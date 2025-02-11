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
        'name': 'Event notification',
        'template': '''This is an automatically generated notification to inform you of an event within the honeypot sensor network.
        
####### Event {{ID}} #######

{{SUMMARY}}

{{DETAILS}}''',
        'variables': {
            'ID': 'Event identification number',
            'SUMMARY': 'Brief tabular event summary',
            'DETAILS': 'Event-specific details'
        },
        'preview': {
            'ID': '12345',
            'SUMMARY': '''Date: 12.08.2020
Time: 13:26:00 (UTC)
Sensor: Head office
Classification: Honeypot connection
Source: 192.168.1.2
Details: SSH''',
            'DETAILS': '''Sensor interaction (Times in UTC):
----------------------------------
13:26:00: SSH: Connection from 192.168.1.2:48102 
13:26:02: SSH: Invalid login attempt (root/1234)
13:26:03: SSH: Connection closed'''
        }
    },
    TemplateType.EMAIL_SENSOR_TIMEOUT: {
        'name': 'Sensor timeout',
        'template': '''This is an automatically generated notification to inform you of an event within the honeypot sensor network.

The sensor "{{SENSOR_NAME}}" with ID {{SENSOR_ID}} has exceeded its update interval of {{UPDATE_INTERVAL}} minute(s) and its tolerance of {{TIMEOUT_TOLERANCE}} minute(s) and therefore lost its connection to the server. It's now reported as offline.''',
        'variables': {
            'SENSOR_NAME': 'Sensor name',
            'SENSOR_ID': 'Sensor ID',
            'UPDATE_INTERVAL': 'Update interval in minutes',
            'TIMEOUT_TOLERANCE': 'Tolerance (in minutes), in addition to the update interval'
        },
        'preview': {
            'SENSOR_NAME': 'Head office',
            'SENSOR_ID': '3',
            'UPDATE_INTERVAL': '20',
            'TIMEOUT_TOLERANCE': '10'
        }
    },
    TemplateType.EMAIL_SUMMARY: {
        'name': 'Weekly event summary',
        'template': '''This is an automatically generated summary of all events that occurred in the sensor network within your groups over the past week.

Time period: {{RANGE_FROM}} to {{RANGE_TO}}

{{EVENTS}}''',
        'variables': {
            'RANGE_FROM': 'Start of the summary period',
            'RANGE_TO': 'End of the summary period',
            'EVENTS': 'List of events that occurred per group'
        },
        'preview': {
            'RANGE_FROM': '03.02.2020',
            'RANGE_TO': '10.02.2020',
            'EVENTS': '''### Group "Management" ###
  Total events: 13, 2 of which were critical
  Events per sensor:
    Head office: 10
    Room C: 3

  Critical events:
    2020-02-05 15:29:05 (ID 9, Sensor "Head office"): Scan from 172.26.0.1 (Scan)
    2020-02-06 14:56:09 (ID 9, Sensor "Head office"): Honeypot connection from 172.26.0.1 (SSH)'''
        }
    },
    TemplateType.EMAIL_CA_EXPIRATION: {
        'name': 'Internal CA certificate expiration',
        'template': '''This is an automatically generated notification sent by the HoneySens server {{SERVER_NAME}}.

The internal CA certificate expires in {{EXPIRATION_TIME}} days and should be renewed in advance. To do this, log in to the server's web interface with an administrative account and follow the instructions under “System” -> "Internal Certificate Authority".

If the certificate expires without being renewed in time, the existing sensors may no longer be able to communicate with the server and require redeployment.''',
        'variables': {
            'SERVER_NAME': 'Hostname of this HoneySens server',
            'EXPIRATION_TIME': 'Time in days until the current CA certificates expires.'
        },
        'preview': {
            'SERVER_NAME': 'honeysens.company.tld',
            'EXPIRATION_TIME': '3'
        }
    },
    TemplateType.EMAIL_HIGH_SYSTEM_LOAD: {
        'name': 'High system load',
        'template': '''This is an automatically generated notification sent by the HoneySens server {{SERVER_NAME}}.

The server is under heavy load, there are currently {{QUEUE_LENGTH}} unprocessed tasks in the queue. This warning was sent because the queue length has exceeded the threshold value of {{QUEUE_THRESHOLD}}.''',
        'variables': {
            'SERVER_NAME': 'Hostname of this HoneySens server',
            'QUEUE_LENGTH': 'Queue length at the time of alarm',
            'QUEUE_THRESHOLD': 'Queue length threshold'
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
