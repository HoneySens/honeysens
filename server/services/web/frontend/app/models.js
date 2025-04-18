import HoneySens from 'app/app';
import 'backbone.paginator';

HoneySens.module('Models', function(Models, HoneySens, Backbone, Marionette, $, _) {
    Models.Event = Backbone.Model.extend({
        urlRoot: 'api/events',
        getDetailsAndPackets: function() {
            let details = new Models.EventDetails(),
                packets = new Models.EventPackets(),
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

    Models.Event.classification = {
        UNKNOWN: 0,
        ICMP: 1,
        CONN_ATTEMPT: 2,
        LOW_HP: 3,
        PORTSCAN: 4
    };

    Models.Event.status = {
        UNEDITED: 0,
        BUSY: 1,
        RESOLVED: 2,
        IGNORED: 3
    };

    Models.Events = Backbone.PageableCollection.extend({
        model: Models.Event,
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

    Models.EventDetail = Backbone.Model.extend({
        initialize: function() {
            var timestamp = this.get('timestamp') == null ? null : new Date(this.get('timestamp') * 1000);
            this.set('timestamp', timestamp);
        }
    });

    Models.EventDetail.type = {
        GENERIC: 0,
        INTERACTION: 1
    };

    Models.EventDetails = Backbone.Collection.extend({
        model: Models.EventDetail
    });

    Models.EventPacket = Backbone.Model.extend({
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

    Models.EventPacket.protocol = {
        UNKNOWN: 0,
        TCP: 1,
        UDP: 2
    };

    Models.EventPackets = Backbone.Collection.extend({
        model: Models.EventPacket
    });

    Models.EventFilterCondition = Backbone.Model.extend({
        defaults: {
            'field': 1,
            'type': 0,
            'value': null
        }
    });

    Models.EventFilterCondition.field = {
        CLASSIFICATION: 0,
        SOURCE: 1,
        TARGET: 2,
        PROTOCOL: 3
    };

    Models.EventFilterCondition.type = {
        SOURCE_STATIC: 0,
        SOURCE_REGEX: 1,
        SOURCE_IPRANGE: 2,
        TARGET_PORT: 3
    };

    Models.EventFilterConditions = Backbone.Collection.extend({
        model: Models.EventFilterCondition
    });

    Models.EventFilter = Backbone.Model.extend({
        urlRoot: 'api/eventfilters',
        defaults: {
            'division': null,
            'name': null,
            'count': 0,
            'conditions': [],
            'enabled': true
        },
        getConditionCollection: function() {
            var conditions = new Models.EventFilterConditions();
            _.each(this.get('conditions'), function(c) {
                conditions.add(new Models.EventFilterCondition(c));
            });
            return conditions;
        }
    });

    Models.EventFilters = Backbone.PageableCollection.extend({
        model: Models.EventFilter,
        url: 'api/eventfilters',
        mode: 'client',
        state: {
            pageSize: 1024
        }
    });

    Models.Sensor = Backbone.Model.extend({
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
            this.status = new Models.SensorStati();
            this.status.sensor = this;
        },
        getFirmware: function() {
            if(this.get('firmware')) return HoneySens.data.models.platforms.getFirmware(this.get('firmware'));
        }
    });

    Models.Sensors = Backbone.PageableCollection.extend({
        model: Models.Sensor,
        url: 'api/sensors',
        mode: 'client',
        state: {
            pageSize: 1024
        }
    });

    Models.SSLCert = Backbone.Model.extend({
        defaults: {
            'content': '',
            'fingerprint': ''
        }
    });

    Models.SSLCerts = Backbone.Collection.extend({
        model: Models.SSLCert,
        url: 'api/certs/'
    });

    Models.Firmware = Backbone.Model.extend({
        defaults: {
            'name': '',
            'version': '',
            'description': '',
            'changelog': ''
        }
    });

    Models.FirmwareCollection = Backbone.Collection.extend({
        model: Models.Firmware,
        url: 'api/platforms/firmware'
    });

    Models.SensorStatus = Backbone.Model.extend({
        initialize: function() {
            this.set('timestamp', new Date(this.get('timestamp') * 1000));
        }
    });

    Models.SensorStatus.status = {
        ERROR: 0,
        RUNNING: 1,
        UPDATING: 2,
        TIMEOUT: 3
    };

    Models.SensorStatus.serviceStatus = {
        RUNNING: 0,
        SCHEDULED: 1,
        ERROR: 2
    };

    Models.SensorStati = Backbone.Collection.extend({
        model: Models.SensorStatus,
        url: function() {
            return 'api/sensors/status/by-sensor/' + this.sensor.id;
        }
    });

    Models.ServiceRevision = Backbone.Model.extend({
        defaults: {
            'revision': '',
            'architecture': '',
            'description': '',
            'service': null
        }
    });

    Models.ServiceRevisions = Backbone.Collection.extend({
        model: Models.ServiceRevision,
        url: 'api/services/revisions'
    });

    Models.ServiceVersion = Backbone.Model.extend({
        defaults: {
            'architectures': [],
            'revisions': []
        },
        getRevisions: function() {
            return new Models.ServiceRevisions(this.get('revisions'));
        }
    });

    Models.ServiceVersions = Backbone.Collection.extend({
        model: Models.ServiceVersion
    });

    Models.Service = Backbone.Model.extend({
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
            return new Models.ServiceVersions(this.get('versions'));
        }
    });

    Models.Services = Backbone.PageableCollection.extend({
        model: Models.Service,
        url: 'api/services',
        mode: 'client'
    });

    Models.Platform = Backbone.Model.extend({
        defaults: {
            'name': '',
            'title': '',
            'description': ''
        },
        getFirmwareRevisions: function() {
            return new Models.FirmwareCollection(this.get('firmware_revisions'));
        }
    });

    Models.Platforms = Backbone.PageableCollection.extend({
        model: Models.Platform,
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
            return new Models.Platforms(filteredPlatforms);
        }
    });

    Models.Division = Backbone.Model.extend({
        defaults: {
            'name': '',
            'users': []
        },
        getUserCollection: function() {
            // TODO move to Users collection, so this no longer depends on global state
            // returns a new collection of user objects that belong to this division
            var users = new Models.Users();
            _.each(this.get('users'), function(u) {
                users.add(HoneySens.data.models.users.get(u));
            });
            return users;
        }
    });

    Models.Divisions = Backbone.Collection.extend({
        model: Models.Division,
        url: 'api/divisions',
        byUser: function(id) {
            return new Models.Divisions(this.filter(function(division) {
                return _.contains(division.get('users'), id);
            }));
        }
    });

    Models.User = Backbone.Model.extend({
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

    Models.User.role = {
        GUEST: 0,
        OBSERVER: 1,
        MANAGER: 2,
        ADMIN: 3
    };

    Models.User.domain = {
        LOCAL: 0,
        LDAP: 1
    };

    Models.Users = Backbone.Collection.extend({
        model: Models.User,
        url: 'api/users'
    });

    Models.IncidentContact = Backbone.Model.extend({
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

    Models.IncidentContact.type = {
        MAIL: 0,
        USER: 1
    };

    Models.IncidentContacts = Backbone.Collection.extend({
        model: Models.IncidentContact,
        url: 'api/contacts/'
    });

    Models.Stats = Backbone.Model.extend({
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
                sensorsOnline = HoneySens.data.models.sensors.filter((m) => m.get('last_status') === Models.SensorStatus.status.RUNNING || m.get('last_status') === Models.SensorStatus.status.UPDATING).length;
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

    Models.TaskWorkerStatus = Backbone.Model.extend({
        defaults: {
            queue_length: 0
        },
        url: 'api/tasks/status'
    });

    Models.Task = Backbone.Model.extend({
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
            if(this.get('status') === Models.Task.status.DONE)
                window.location.href = '/api/tasks/' + this.id + '/result/' + removalFlag;
        }
    });

    Models.Task.type = {
        SENSORCFG_CREATOR: 0,
        UPLOAD_VERIFIER: 1,
        REGISTRY_MANAGER: 2,
        EVENT_EXTRACTOR: 3,
        EVENT_FORWARDER: 4,
        EMAIL_EMITTER: 6
    };

    Models.Task.status = {
        SCHEDULED: 0,
        RUNNING: 1,
        DONE: 2,
        ERROR: 3
    };

    Models.Tasks = Backbone.Collection.extend({
        model: Models.Task,
        url: 'api/tasks/'
    });

    Models.LogEntry = Backbone.Model.extend({
        defaults: {
            timestamp: null,
            user_id: null,
            resource_id: null,
            resource_type: 0,
            message: ''
        }
    });

    Models.LogEntry.resource = {
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

    Models.Logs = Backbone.PageableCollection.extend({
        model: Models.LogEntry,
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

    Models.Settings = {
        encryption: {
            NONE: 0,
            STARTTLS: 1,
            TLS: 2
        },
        transport: {
            UDP: 0,
            TCP: 1
        }
    };

    Models.Template = Backbone.Model.extend({
        idAttribute: 'type',
        defaults: {
            name: '',
            template: '',
            variables: {},
            overlay: null
        }
    });

    Models.Templates = Backbone.Collection.extend({
        model: Models.Template,
        url: 'api/templates'
    });

    // Initialize runtime models
    HoneySens.addInitializer(function() {
        HoneySens.data.models.sensors = new Models.Sensors();
        HoneySens.data.models.events = new Models.Events([], {state: {totalRecords: 0}});
        HoneySens.data.models.new_events = new Models.Events([], {state: {totalRecords: 0}});  // Tracks yet unseen events
        HoneySens.data.models.eventfilters = new Models.EventFilters();
        HoneySens.data.models.users = new Models.Users();
        HoneySens.data.models.divisions = new Models.Divisions();
        HoneySens.data.models.certs = new Models.SSLCerts();
        HoneySens.data.models.contacts = new Models.IncidentContacts();
        HoneySens.data.models.services = new Models.Services();
        HoneySens.data.models.platforms = new Models.Platforms();
        HoneySens.data.models.tasks = new Models.Tasks();
        HoneySens.data.models.logs = new Models.Logs([], {state: {totalRecords: 0}});
        HoneySens.data.session.user = new Models.User();
    });
});

export default HoneySens.Models;