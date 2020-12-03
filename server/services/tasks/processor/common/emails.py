import smtplib
from email.mime.text import MIMEText

TRANSPORT_SECURITY_NONE = 0
TRANSPORT_SECURITY_STARTTLS = 1
TRANSPORT_SECURITY_TLS = 2


def send_email_raw(smtp_host, smtp_port, msg_from, msg_to, msg_subject, msg_body,
               smtp_user=None, smtp_password=None, transport_security=TRANSPORT_SECURITY_NONE):
    """Assembles and sends an E-Mail with the given parameters."""
    # Canonize params
    smtp_port = int(smtp_port)
    transport_security = int(transport_security)
    # Compose message
    msg = MIMEText(msg_body)
    msg['Subject'] = msg_subject
    msg['From'] = msg_from
    msg['To'] = msg_to
    # Connect
    if transport_security == TRANSPORT_SECURITY_TLS:
        smtp_conn = smtplib.SMTP_SSL(smtp_host, smtp_port)
    else:
        smtp_conn = smtplib.SMTP(smtp_host, smtp_port)
    with smtp_conn as server:
        if transport_security == TRANSPORT_SECURITY_STARTTLS:
            server.starttls()
        if smtp_user is not None:
            server.login(smtp_user, smtp_password)
        server.sendmail(msg_from, msg_to, msg.as_string())


def send_email(config, msg_to, msg_subject, msg_body):
    """Wrapper for send_email_raw, fetches SMTP configuration from the given config."""
    if not config.getboolean('smtp', 'enabled'):
        return
    # Determine credentials
    smtp_user = config.get('smtp', 'user')
    smtp_password = config.get('smtp', 'password')
    if len(smtp_user) == 0:
        smtp_user = None
        smtp_password = None
    # Determine transport security (TLS/STARTTLS/None)
    transport_security = config.getint('smtp', 'encryption')
    send_email_raw(config.get('smtp', 'server'), config.get('smtp', 'port'), config.get('smtp', 'from'), msg_to, msg_subject, msg_body,
                   smtp_user, smtp_password, transport_security)
