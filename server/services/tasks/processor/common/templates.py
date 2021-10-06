from enum import IntEnum


class TemplateType(IntEnum):
    EMAIL_EVENT_NOTIFICATION = 0


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
Zeit: 13:26:00
Sensor: Zentrale
Klassifikation: Honeypot-Verbindung
Quelle: 192.168.1.2
Details: SSH''',
            'DETAILS': '''Sensorinteraktion:
--------------------------
13:26:00: SSH: Connection from 192.168.1.2:48102 
13:26:02: SSH: Invalid login attempt (root/1234)
13:26:03: SSH: Connection closed'''
        }
    }
}
