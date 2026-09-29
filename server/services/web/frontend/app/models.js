import Backbone from 'backbone';
import HoneySens from 'app/app';
import PageableCollection from 'backbone.paginator';

export const Event = Backbone.Model.extend({
    urlRoot: 'api/events',
    getDetailsAndPackets: function() {
        let details = new EventDetails(),
            packets = new EventPackets(),
            subURL = this.get('archived') ? 'by-archived-event' : 'by-event';

        $.ajax({
            method: 'GET',
            url: 'api/eventdetails/' + subURL + '/' + this.id,
            success: function(data) {
                data = JSON.parse(data);
                details.reset(data.details);
                packets.reset(data.packets);
            }
        });
        return {
            details: details,
            packets: packets
        };
    }
});

export const EventClassification = {
    UNKNOWN: 0,
    ICMP: 1,
    CONN_ATTEMPT: 2,
    LOW_HP: 3,
    PORTSCAN: 4
};

export const EventStatus = {
    UNEDITED: 0,
    BUSY: 1,
    RESOLVED: 2,
    IGNORED: 3
};

export const Events = PageableCollection.extend({
    model: Event,
    mode: 'server',
    url: function() {
        if(this.length > 0) {
            return 'api/events?last_id=' + (this.last().get('id'));
        } else {
            return 'api/events';
        }
    },
    state: {
        firstPage: 0,
        pageSize: 15,
        sortKey: 'timestamp',
        order: 1
    },
    parseState: function(resp, queryParams, state, options) {
        return {totalRecords: parseInt(resp.total_count)};
    },
    parseRecords: function(resp, options) {
        return resp.items;
    }
});

export const EventDetail = Backbone.Model.extend({
    initialize: function() {
        var timestamp = this.get('timestamp') == null ? null : new Date(this.get('timestamp') * 1000);
        this.set('timestamp', timestamp);
    }
});

export const EventDetailType = {
    GENERIC: 0,
    INTERACTION: 1
};

export const EventDetails = Backbone.Collection.extend({
    model: EventDetail
});

export const EventPacket = Backbone.Model.extend({
    defaults: {
        timestamp: '',
        protocol: 0,
        port: 0,
        headers: '',
        payload: ''
    },
    initialize: function() {
        this.set('timestamp', new Date(this.get('timestamp') * 1000));
    }
});

export const EventPacketProtocol = {
    UNKNOWN: 0,
    TCP: 1,
    UDP: 2
};

export const EventPackets = Backbone.Collection.extend({
    model: EventPacket
});

export const EventFilterCondition = Backbone.Model.extend({
    defaults: {
        'field': 1,
        'type': 0,
        'value': null
    }
});

export const EventFilterConditionField = {
    CLASSIFICATION: 0,
    SOURCE: 1,
    TARGET: 2,
    PROTOCOL: 3
};

export const EventFilterConditionType = {
    SOURCE_STATIC: 0,
    SOURCE_REGEX: 1,
    SOURCE_IPRANGE: 2,
    TARGET_PORT: 3
};

export const EventFilterConditions = Backbone.Collection.extend({
    model: EventFilterCondition
});

export const EventFilter = Backbone.Model.extend({
    urlRoot: 'api/eventfilters',
    defaults: {
        'division': null,
        'name': null,
        'count': 0,
        'conditions': [],
        'enabled': true
    },
    getConditionCollection: function() {
        var conditions = new EventFilterConditions();
        _.each(this.get('conditions'), function(c) {
            conditions.add(new EventFilterCondition(c));
        });
        return conditions;
    }
});

export const EventFilters = PageableCollection.extend({
    model: EventFilter,
    url: 'api/eventfilters',
    mode: 'client',
    state: {
        pageSize: 1024
    }
});

export const Sensor = Backbone.Model.extend({
    urlRoot: 'api/sensors',
    status: null,
    defaults: {
        'hostname': '',
        'name' : '',
        'location': '',
        'division': null,
        'eapol_mode': 0,
        'eapol_identity': null,
        'eapol_anon_identity': null,
        'eapol_ca_cert': null,
        'eapol_client_cert': null,
        'eapol_client_key': null,
        'update_interval': null,
        'last_status': '',
        'last_status_since': null,
        'last_status_ts': null,
        'sw_version': '',
        'last_ip' : '',
        'server_endpoint_mode': 0,
        'server_endpoint_host': null,
        'server_endpoint_port_https': null,
        'network_ip_mode': 0,
        'network_ip_address': null,
        'network_ip_netmask': null,
        'network_mac_mode': 0,
        'network_mac_address': null,
        'network_dhcp_hostname': null,
        'new_events': 0,
        'proxy_mode': 0,
        'proxy_host': null,
        'proxy_port': null,
        'proxy_user': null,
        'firmware': null,
        'services': [],
        'service_network': null
    },
    initialize: function() {
        this.status = new SensorStati();
        this.status.sensor = this;
    },
    getFirmware: function() {
        if(this.get('firmware')) return HoneySens.data.models.platforms.getFirmware(this.get('firmware'));
    }
});

export const Sensors = PageableCollection.extend({
    model: Sensor,
    url: 'api/sensors',
    mode: 'client',
    state: {
        pageSize: 1024
    }
});

export const SSLCert = Backbone.Model.extend({
    defaults: {
        'content': '',
        'fingerprint': ''
    }
});

export const SSLCerts = Backbone.Collection.extend({
    model: SSLCert,
    url: 'api/certs/'
});

export const Firmware = Backbone.Model.extend({
    defaults: {
        'name': '',
        'version': '',
        'description': '',
        'changelog': ''
    }
});

export const FirmwareCollection = Backbone.Collection.extend({
    model: Firmware,
    url: 'api/platforms/firmware'
});

export const SensorStatus = Backbone.Model.extend({
    initialize: function() {
        this.set('timestamp', new Date(this.get('timestamp') * 1000));
    }
});

export const SensorStatusFlag = {
    ERROR: 0,
    RUNNING: 1,
    UPDATING: 2,
    TIMEOUT: 3
};

export const ServiceStatusFlag = {
    RUNNING: 0,
    SCHEDULED: 1,
    ERROR: 2
};

export const SensorStati = Backbone.Collection.extend({
    model: SensorStatus,
    url: function() {
        return 'api/sensors/status/by-sensor/' + this.sensor.id;
    }
});

export const ServiceRevision = Backbone.Model.extend({
    defaults: {
        'revision': '',
        'architecture': '',
        'description': '',
        'service': null
    }
});

export const ServiceRevisions = Backbone.Collection.extend({
    model: ServiceRevision,
    url: 'api/services/revisions'
});

export const ServiceVersion = Backbone.Model.extend({
    defaults: {
        'architectures': [],
        'revisions': []
    },
    getRevisions: function() {
        return new ServiceRevisions(this.get('revisions'));
    }
});

export const ServiceVersions = Backbone.Collection.extend({
    model: ServiceVersion
});

export const Service = Backbone.Model.extend({
    urlRoot: 'api/services',
    defaults: {
        'name': '',
        'description': '',
        'repository': '',
        'versions': [],
        'default_revision': null,
        'assignments': []
    },
    getVersions: function() {
        return new ServiceVersions(this.get('versions'));
    }
});

export const Services = PageableCollection.extend({
    model: Service,
    url: 'api/services',
    mode: 'client'
});

export const Platform = Backbone.Model.extend({
    defaults: {
        'name': '',
        'title': '',
        'description': ''
    },
    getFirmwareRevisions: function() {
        return new FirmwareCollection(this.get('firmware_revisions'));
    }
});

export const Platforms = PageableCollection.extend({
    model: Platform,
    url: 'api/platforms',
    mode: 'client',
    getFirmware: function(id) {
        var needle = parseInt(id),
            result;
        this.forEach(function(p) {
            p.getFirmwareRevisions().forEach(function(r) {
                if(r.id === needle) {
                    result = r;
                }
            });
        });
        return result;
    },
    byFirmwareAvailability: function() {
        var filteredPlatforms = this.filter(function(p) {
            return _.size(p.get('firmware_revisions')) > 0;
        });
        return new Platforms(filteredPlatforms);
    }
});

export const Division = Backbone.Model.extend({
    defaults: {
        'name': '',
        'users': []
    },
    getUserCollection: function() {
        // TODO move to Users collection, so this no longer depends on global state
        // returns a new collection of user objects that belong to this division
        var users = new Users();
        _.each(this.get('users'), function(u) {
            users.add(HoneySens.data.models.users.get(u));
        });
        return users;
    }
});

export const Divisions = Backbone.Collection.extend({
    model: Division,
    url: 'api/divisions',
    byUser: function(id) {
        return new Divisions(this.filter(function(division) {
            return _.contains(division.get('users'), id);
        }));
    }
});

export const User = Backbone.Model.extend({
    defaults: {
        'name': '',
        'domain': 0,
        'full_name': '',
        'email': '',
        'password': '',
        'role': 1,
        'divisions': [],
        'permissions': [],
        'notify_on_system_state': false,
        'require_password_change': false
    }
});

export const UserRole = {
    GUEST: 0,
    OBSERVER: 1,
    MANAGER: 2,
    ADMIN: 3
};

export const UserDomain = {
    LOCAL: 0,
    LDAP: 1
};

export const Users = Backbone.Collection.extend({
    model: User,
    url: 'api/users'
});

export const IncidentContact = Backbone.Model.extend({
    defaults: {
        'division': null,
        'email': null,
        'user': null,
        'sendWeeklySummary': false,
        'sendCriticalEvents': false,
        'sendAllEvents': false,
        'sendSensorTimeouts': false,
        'type': 0
    }
});

export const IncidentContactType = {
    MAIL: 0,
    USER: 1
};

export const IncidentContacts = Backbone.Collection.extend({
    model: IncidentContact,
    url: 'api/contacts/'
});

export const Stats = Backbone.Model.extend({
    defaults: {
        year: '',
        month: null,
        division: null,
        events_timeline: [],
        events_total: 0,
        events_live: 0,
        events_unedited: 0,
        events_busy: 0,
        events_resolved: 0,
        events_ignored: 0,
        events_archived: 0,
        sensors_total: 0,
        sensors_online: 0,
        sensors_offline: 0,
        filters_total: 0,
        filters_active: 0,
        filters_inactive: 0,
        services_total: 0,
        services_online: 0,
        services_offline: 0,
        users: 0,
        divisions: 0
    },
    url: 'api/stats',
    recalculate: function() {
        var sensorsTotal = HoneySens.data.models.sensors.length,
            sensorsOnline = HoneySens.data.models.sensors.filter((m) => m.get('last_status') === SensorStatusFlag.RUNNING || m.get('last_status') === SensorStatusFlag.UPDATING).length;
        this.set('sensors_total', sensorsTotal);
        this.set('sensors_online', sensorsOnline);
        this.set('sensors_offline', sensorsTotal - sensorsOnline);
        var filtersTotal = HoneySens.data.models.eventfilters.length,
            filtersActive = HoneySens.data.models.eventfilters.where({enabled: true}).length;
        this.set('filters_total', filtersTotal);
        this.set('filters_active', filtersActive);
        this.set('filters_inactive', filtersTotal - filtersActive);
        var servicesTotal = HoneySens.data.models.sensors.map((m) => m.get('services').length).reduce((acc, val) => acc + val, 0),
            servicesOnline = HoneySens.data.models.sensors.map((m) => {
                var lastServiceStatus = m.get('last_service_status');
                if(lastServiceStatus === null) return 0;
                return Object.keys(lastServiceStatus).filter(key => lastServiceStatus[key] === 0).length;
            }).reduce((acc, val) => acc + val, 0);
        this.set('services_total', servicesTotal);
        this.set('services_online', servicesOnline);
        this.set('services_offline', servicesTotal - servicesOnline);
        this.set('users', HoneySens.data.models.users.length);
        this.set('divisions', HoneySens.data.models.divisions.length);
    }
});

export const TaskWorkerStatus = Backbone.Model.extend({
    defaults: {
        queue_length: 0
    },
    url: 'api/tasks/status'
});

export const Task = Backbone.Model.extend({
    urlRoot: 'api/tasks',
    defaults: {
        user: null,
        type: 0,
        status: 0,
        params: {},
        result: {}
    },
    downloadResult: function(removeAfterwards) {
        var removalFlag = removeAfterwards ? '1' : '0';
        if(this.get('status') === TaskStatus.DONE)
            window.location.href = '/api/tasks/' + this.id + '/result/' + removalFlag;
    }
});

export const TaskType = {
    SENSORCFG_CREATOR: 0,
    UPLOAD_VERIFIER: 1,
    REGISTRY_MANAGER: 2,
    EVENT_EXTRACTOR: 3,
    EVENT_FORWARDER: 4,
    EMAIL_EMITTER: 6
};

export const TaskStatus = {
    SCHEDULED: 0,
    RUNNING: 1,
    DONE: 2,
    ERROR: 3
};

export const Tasks = Backbone.Collection.extend({
    model: Task,
    url: 'api/tasks/'
});

export const LogEntry = Backbone.Model.extend({
    defaults: {
        timestamp: null,
        user_id: null,
        resource_id: null,
        resource_type: 0,
        message: ''
    }
});

export const LogEntryResource = {
    GENERIC: 0,
    CONTACTS: 1,
    DIVISIONS: 2,
    EVENTFILTERS: 3,
    EVENTS: 4,
    PLATFORMS: 5,
    SENSORS: 6,
    SERVICES: 7,
    SETTINGS: 8,
    TASKS: 9,
    USERS: 10,
    SYSTEM: 11,
    SESSIONS: 12
};

export const Logs = PageableCollection.extend({
    model: LogEntry,
    mode: 'server',
    url: 'api/logs/',
    state: {
        firstPage: 0,
        pageSize: 15,
        sortKey: 'timestamp',
        order: 1
    },
    parseState: function(resp, queryParams, state, options) {
        return {totalRecords: parseInt(resp.total_count)};
    },
    parseRecords: function(resp, options) {
        return resp.items;
    }
});

export const ChannelEncryption = {
    NONE: 0,
    STARTTLS: 1,
    TLS: 2
}

export const TransportProtocol = {
    UDP: 0,
    TCP: 1
}

export const Template = Backbone.Model.extend({
    idAttribute: 'type',
    defaults: {
        name: '',
        template: '',
        variables: {},
        overlay: null
    }
});

export const Templates = Backbone.Collection.extend({
    model: Template,
    url: 'api/templates'
});

// Initialize runtime models
HoneySens.addInitializer(function() {
    HoneySens.data.models.sensors = new Sensors();
    HoneySens.data.models.events = new Events([], {state: {totalRecords: 0}});
    HoneySens.data.models.new_events = new Events([], {state: {totalRecords: 0}});  // Tracks yet unseen events
    HoneySens.data.models.eventfilters = new EventFilters();
    HoneySens.data.models.users = new Users();
    HoneySens.data.models.divisions = new Divisions();
    HoneySens.data.models.certs = new SSLCerts();
    HoneySens.data.models.contacts = new IncidentContacts();
    HoneySens.data.models.services = new Services();
    HoneySens.data.models.platforms = new Platforms();
    HoneySens.data.models.tasks = new Tasks();
    HoneySens.data.models.logs = new Logs([], {state: {totalRecords: 0}});
    HoneySens.data.session.user = new User();
});