import configparser
from datetime import datetime, timedelta

from ..common import emails
from .handler import HandlerInterface


class WeeklySummarizer(HandlerInterface):
    """Sends summary mails of events that occurred within the last week to all users that opted into it.
    This module respects division assignment, which means each recipient only receives data for each division
    he's been assigned to."""

    CRITICAL_EVENT_CAP = 100  # How many critical events to append (upper bound)
    EVENT_CLASSIFICATIONS = {
        0: 'Unbekannt',
        1: 'ICMP-Paket',
        2: 'Verbindungsversuch',
        3: 'Honeypot-Verbindung',
        4: 'Portscan'
    }

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        if not config.getboolean('smtp', 'enabled'):
            return {}
        candidates = {}  # recipient -> [division_ids]
        with db.cursor() as cur:
            cur.execute(('SELECT c.division_id,c.email,u.email FROM contacts AS c '
                         'LEFT JOIN users AS u ON c.user_id = u.id ' ''
                         'WHERE c.sendWeeklySummary = "1"'))
            rows = cur.fetchall()
            for row in rows:
                recipient = row['u.email'] if row['u.email'] is not None else row['email']
                candidates.setdefault(recipient, set()).add(row['division_id'])
        self._send_weekly_summaries(logger, db, config, candidates)
        return {}

    def _send_weekly_summaries(self, logger, db, config, candidates):
        """Takes a dict of recipients with their associated divisions to generate and send weekly summaries."""
        division_summaries = {}  # division_id -> str(summary)
        range_end = datetime.now()
        range_start = range_end - timedelta(days=7)
        range_end_str = range_end.strftime('%d.%m.%Y')
        range_start_str = range_start.strftime('%d.%m.%Y')
        for recipient, divisions in candidates.items():
            has_any_content = False
            subject = 'HoneySens: Zusammenfassung des Zeitraums vom {} bis {}'.format(range_start_str, range_end_str)
            body = ('Dies ist eine automatisch generierte Zusammenfassung der im Sensornetzwerk in Ihren Gruppen '
                    'aufgetretenen Ereignisse der vergangenen Woche.\n\n'
                    'Zeitraum: {} bis {}\n\n'.format(range_start_str, range_end_str))
            for d in divisions:
                if d not in division_summaries:
                    division_summaries[d] = self._summarize_weekly_division(db, d)
                if division_summaries[d] is not None:
                    has_any_content = True
                    body += '{}\n'.format(division_summaries[d])
            if has_any_content:
                logger.info('Sending summary E-Mail to {}'.format(recipient))
                emails.send_email(config, recipient, subject, body)

    def _summarize_weekly_division(self, db, division):
        """Returns a string that summarizes the given division (id) over the last week."""
        events = []
        critical_events = []
        event_cnt_per_sensor = {}
        division_name = None
        with db.cursor() as cur:
            cur.execute(('SELECT d.name,s.name,e.id,e.timestamp,e.classification,e.source,e.summary FROM events AS e '
                         'INNER JOIN sensors AS s ON e.sensor_id = s.id '
                         'INNER JOIN divisions AS d ON s.division_id = d.id '
                         'WHERE d.id = %s AND e.timestamp >= DATE(NOW()) - INTERVAL 7 DAY'), division)
            rows = cur.fetchall()
            for row in rows:
                division_name = row['name']
                events.append(row)
                event_cnt_per_sensor.setdefault(row['s.name'], 0)
                event_cnt_per_sensor[row['s.name']] += 1
                if row['classification'] >= 3:
                    critical_events.append(row)
        # Only return something if we found events
        if division_name is not None:
            result = '### Gruppe "{}" ###\n'.format(division_name)
            result += '  Ereignisse insgesamt: {}, davon {} kritisch\n'.format(len(events), len(critical_events))
            result += '  Ereignisse pro Sensor:\n'
            for sensor, count in event_cnt_per_sensor.items():
                result += '    {}: {}\n'.format(sensor, count)
            result += '\n  Kritische Ereignisse:\n'
            for event in critical_events[:self.CRITICAL_EVENT_CAP]:
                date = event['timestamp'].strftime('%Y-%m-%d %H:%M:%S')
                result += '    {} (ID {}, Sensor {}): {} von {} ({})\n'.format(date, event['id'], event['s.name'],
                                                                               self.EVENT_CLASSIFICATIONS[
                                                                                   event['classification']],
                                                                               event['source'], event['summary'])
            if len(critical_events) > self.CRITICAL_EVENT_CAP:
                result += '    ... und {} weitere Ereignisse\n'.format(len(critical_events) - self.CRITICAL_EVENT_CAP)
            return result