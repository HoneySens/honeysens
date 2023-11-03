from enum import IntEnum
from pymysql.connections import Connection


class TemplateType(IntEnum):
    EMAIL_EVENT_NOTIFICATION = 0
    EMAIL_SENSOR_TIMEOUT = 1
    EMAIL_SUMMARY = 2
    EMAIL_CA_EXPIRATION = 3
    EMAIL_HIGH_SYSTEM_LOAD = 4


SYSTEM_NOTIFICATION_TEMPLATES = {
    TemplateType.EMAIL_EVENT_NOTIFICATION: {
        'name': 'Ereignis-Benachrichtigung',
        'template': '''Dies ist eine automatisch generierte Nachricht vom HoneySens-System, um auf einen Vorfall innerhalb des Sensornetzwerkes hinzuweisen. Details entnehmen Sie der nachfolgenden Auflistung.
        
####### Vorfall {{ID}} #######

{{SUMMARY}}

{{DETAILS}}''',
        'variables': {
            'ID': 'Identifikationsnummer des Ereignisses',
            'SUMMARY': 'Tabellarische Kurzzusammenfassung des Ereignisses',
            'DETAILS': 'Ereignisspezifische Details'
        },
        'preview': {
            'ID': '12345',
            'SUMMARY': '''Datum: 12.08.2020
Zeit: 13:26:00 (UTC)
Sensor: Zentrale
Klassifikation: Honeypot-Verbindung
Quelle: 192.168.1.2
Details: SSH''',
            'DETAILS': '''Sensorinteraktion (Zeiten in UTC):
----------------------------------
13:26:00: SSH: Connection from 192.168.1.2:48102 
13:26:02: SSH: Invalid login attempt (root/1234)
13:26:03: SSH: Connection closed'''
        }
    },
    TemplateType.EMAIL_SENSOR_TIMEOUT: {
        'name': 'Sensor-Timeout',
        'template': '''Dies ist eine automatisch generierte Hinweismail über ein Ereignis im Sensornetzwerk.

Der Sensor "{{SENSOR_NAME}}" mit der ID {{SENSOR_ID}} hat zu lange nicht mehr den Server kontaktiert und somit sein Update-Intervall von {{UPDATE_INTERVAL}} Minute(n) zzgl. der Toleranzzeit von {{TIMEOUT_TOLERANCE}} Minute(n) überschritten. Er ist nun offline.''',
        'variables': {
            'SENSOR_NAME': 'Name des betroffenen Sensors',
            'SENSOR_ID': 'ID des betroffenen Sensors',
            'UPDATE_INTERVAL': 'Update-Intervall in Minuten',
            'TIMEOUT_TOLERANCE': 'Toleranzzeit zzgl. zum Update-Intervall (in Minuten)'
        },
        'preview': {
            'SENSOR_NAME': 'Zentrale',
            'SENSOR_ID': '3',
            'UPDATE_INTERVAL': '20',
            'TIMEOUT_TOLERANCE': '10'
        }
    },
    TemplateType.EMAIL_SUMMARY: {
        'name': 'Wöchentliche Ereignisübersicht',
        'template': '''Dies ist eine automatisch generierte Zusammenfassung der im Sensornetzwerk in Ihren Gruppen aufgetretenen Ereignisse der vergangenen Woche.

Zeitraum: {{RANGE_FROM}} bis {{RANGE_TO}}

{{EVENTS}}''',
        'variables': {
            'RANGE_FROM': 'Start des Zeitraums der Zusammenfassung',
            'RANGE_TO': 'Ende des Zeitraums der Zusammenfassung',
            'EVENTS': 'Auflistung der aufgetretenen Ereignisse pro Gruppe'
        },
        'preview': {
            'RANGE_FROM': '03.02.2020',
            'RANGE_TO': '10.02.2020',
            'EVENTS': '''### Gruppe "Verwaltung" ###
  Ereignisse insgesamt: 13, davon 2 kritisch
  Ereignisse pro Sensor:
    Zentrale: 10
    Keller: 3

  Kritische Ereignisse:
    2020-02-05 15:29:05 (ID 9, Sensor Zentrale): Portscan von 172.26.0.1 (Scan)
    2020-02-06 14:56:09 (ID 9, Sensor Zentrale): Honeypot-Verbindung von 172.26.0.1 (SSH)'''
        }
    },
    TemplateType.EMAIL_CA_EXPIRATION: {
        'name': 'Ablauf des internen CA-Zertifikats',
        'template': '''Dies ist eine automatisch generierte Hinweismail des HoneySens-Servers {{SERVER_NAME}}.

Das interne CA-Zertifikat läuft in {{EXPIRATION_TIME}} Tagen ab und sollte zuvor unbedingt erneuert werden. Melden Sie sich hierzu mit einem administrativen Account an der Weboberfläche des Servers an und folgen Sie den Anweisungen unter "System" -> "Certificate Authority".

Nach Ablauf des Zertifikats ohne rechtzeitige Verlängerung können die bestehenden Sensoren nicht mehr mit dem Server kommunizieren und müssen neu aufgesetzt werden.''',
        'variables': {
            'SERVER_NAME': 'Hostname dieses HoneySens-Servers',
            'EXPIRATION_TIME': 'Zeitraum bis zum Ablauf des CA-Zertifikats in Tagen'
        },
        'preview': {
            'SERVER_NAME': 'honeysens.company.tld',
            'EXPIRATION_TIME': '3'
        }
    },
    TemplateType.EMAIL_HIGH_SYSTEM_LOAD: {
        'name': 'Hohe Systemlast auf Server',
        'template': '''Dies ist eine automatisch generierte Hinweismail des HoneySens-Servers {{SERVER_NAME}}.

Der Server ist stark ausgelastet, es befinden sich derzeit {{QUEUE_LENGTH}} unbearbeitete Aufgaben in der Warteschlange. Diese Warnung wurde versendet, da die Warteschlangenlänge den Grenzwert von {{QUEUE_THRESHOLD}} überschritten hat.''',
        'variables': {
            'SERVER_NAME': 'Hostname dieses HoneySens-Servers',
            'QUEUE_LENGTH': 'Warteschlangenlänge zum Zeitpunkt des Alarms',
            'QUEUE_THRESHOLD': 'Schwellwert der Warteschlangenlänge'
        },
        'preview': {
            'SERVER_NAME': 'honeysens.company.tld',
            'QUEUE_LENGTH': '100',
            'QUEUE_THRESHOLD': '32'
        }
    }
}


def get_template(db: Connection, template_type: int) -> dict:
    """Returns the template of the given type, factoring in a potential overlay"""
    template = SYSTEM_NOTIFICATION_TEMPLATES[template_type].copy()
    cursor = db.cursor()
    cursor.execute('SELECT template FROM template_overlays WHERE type = {}'.format(template_type))
    overlay_db = cursor.fetchone()
    if overlay_db:
        template['template'] = overlay_db['template']
    cursor.close()
    return template


def process_template(db: Connection, template_type: int, data: dict) -> str:
    """Processes and return the requested template by substituting all template variables with the given data values."""
    result = get_template(db, template_type)['template']
    for var, val in data.items():
        result = result.replace('{{' + var + '}}', str(val))
    return result
