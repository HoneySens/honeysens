import configparser
from datetime import datetime, timedelta
import json

from .handler import HandlerInterface


class ArchiveCaretaker(HandlerInterface):
    """Handles automatic archiving of resolved and ignored events (time threshold is configurable),
       as well as cleaning archived events older than a certain threshold (also configurable)."""

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        # Fetch thresholds
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        archive_move_days = config.getint('misc', 'archive_move_days')
        archive_keep_days = config.getint('misc', 'archive_keep_days')
        # Remove archived events older than the given threshold (disabled if 0)
        if archive_keep_days > 0:
            keep_threshold = datetime.now() - timedelta(days=archive_keep_days)
            with db.cursor() as cur:
                row_cnt = cur.execute('DELETE FROM archived_events WHERE archiveTime <= DATE(NOW()) - INTERVAL %s DAY',
                                      archive_keep_days)
                db.commit()
                if row_cnt > 0:
                    logger.info('Removed {} archived events older than {}'.format(row_cnt, keep_threshold.isoformat(
                        sep=' ', timespec='minutes')))
        # Move resolved and ignored events to the archive older than the given threshold (disabled if 0)
        if archive_move_days > 0:
            with db.cursor() as cur:
                cur.execute(('SELECT e.id, s.division_id, e.timestamp, s.name, e.service, e.classification, e.source, e.summary, e.status, e.comment, e.lastModificationTime FROM events AS e JOIN sensors AS s ON e.sensor_id = s.id WHERE '
                             '(status = 2 OR status = 3) AND '
                             'lastModificationTime <= DATE(NOW()) - INTERVAL %s DAY'), archive_move_days)
                events = cur.fetchall()
                for event in events:
                    cur.execute('SELECT * FROM event_details WHERE event_id = %s', event['id'])
                    details = []
                    for detail in cur.fetchall():
                        details.append({
                            'id': detail['id'],
                            'timestamp': int(detail['timestamp'].timestamp()),
                            'type': detail['type'],
                            'data': detail['data']
                        })
                    cur.execute('SELECT * from event_packets WHERE event_id = %s', event['id'])
                    packets = []
                    for packet in cur.fetchall():
                        packets.append({
                            'id': packet['id'],
                            'timestamp': int(packet['timestamp'].timestamp()),
                            'protocol': packet['protocol'],
                            'port': packet['port'],
                            'headers': packet['headers'],
                            'payload': packet['payload']
                        })
                    insert_sql = ('INSERT INTO archived_events (division_id, oid, timestamp, sensor, divisionName, '
                                  'service, classification, source, summary, status, comment, lastModificationTime, '
                                  'details, packets, archiveTime) VALUES (%s, %s, %s, %s, NULL, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())')
                    cur.execute(insert_sql, (
                        event['division_id'],
                        event['id'],
                        event['timestamp'],
                        event['name'],
                        event['service'],
                        event['classification'],
                        event['source'],
                        event['summary'],
                        event['status'],
                        event['comment'],
                        event['lastModificationTime'],
                        json.dumps(details),
                        json.dumps(packets)))
                    cur.execute('DELETE FROM event_details WHERE event_id = %s', event['id'])
                    cur.execute('DELETE FROM event_packets WHERE event_id = %s', event['id'])
                    cur.execute('DELETE FROM events WHERE id = %s', event['id'])
                    db.commit()
                if len(events) > 0:
                    logger.info('Moved {} events older than {} days to the archive'.format(len(events), archive_move_days))
        return {}
