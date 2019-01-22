define(['app/app',
        'tpl!app/modules/sensors/templates/SensorEdit.tpl',
        'app/views/common',
        'validate'],
function(HoneySens, SensorEditTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.SensorEdit = Marionette.ItemView.extend({
            template: SensorEditTpl,
            className: 'container-fluid',
            validators: {
                notEmpty: {
                    validators: {
                        notEmpty: {}
                    }
                },
                networkIP: {
                    validators: {
                        notEmpty: {},
                        ip: {ipv4: true, ipv6: false}
                    }
                },
                networkIPOptional: {
                    validators: {
                        ip: {ipv4: true, ipv6: false}
                    }
                },
                PortNumber: {
                    validators: {
                        notEmpty: {},
                        between: {min: 0, max: 65535, message: 'Gültige Portnummern: 0-65535'}
                    }
                },
                MacAddress: {
                    validators: {
                        notEmpty: {},
                        mac: {}
                    }
                }
            },
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                },
                'change input[name="firmwarePreference"]': function(e) {
                    this.refreshFirmwarePreference(e.target.value);
                },
                'change select[name="firmwarePlatform"]': function(e) {
                    this.refreshFirmwareRevisionSelector(HoneySens.data.models.platforms.get(e.target.value));
                },
                'change input[name="serverEndpoint"]': function(e) {
                    this.refreshServerEndpoint(e.target.value);
                },
                'change input[name="networkMode"]': function(e) {
                    this.refreshNetworkMode(e.target.value);
                },
                'change input[name="networkMACMode"]': function(e) {
                    this.refreshNetworkMAC(e.target.value);
                },
                'change input[name="proxyType"]': function(e) {
                    this.refreshProxy(e.target.value);
                },
                'click button:submit': function(e) {
                    e.preventDefault();
                    this.$el.find('form').bootstrapValidator('validate');
                },
                'click label.disabled': function() {
                    // Ignore clicks on disabled labels. This fixes a bootstrap bug.
                    // See: https://github.com/twbs/bootstrap/issues/16703
                    return false;
                }
            },
            modelEvents: {
                change: function() {
                    // Render the config archive download when it's ready
                    if(this.model.get('config_archive_status') == '3') {
                        this.$el.find('div.configArchive h5').addClass('hide');
                        this.$el.find('div.configArchive a').attr('href', 'api/sensors/config/' + this.model.id).removeClass('hide');
                    }
                }
            },
            onRender: function() {
                var view = this;
                // Busy view spinner
                this.$el.find('div.loading').html(HoneySens.Views.spinner.spin().el);
                // Initialize validation
                this.$el.find('form').bootstrapValidator({
                    feedbackIcons: {
                        valid: 'glyphicon glyphicon-ok',
                        invalid: 'glyphicon glyphicon-remove',
                        validating: 'glyphicon glyphicon-refresh'
                    },
                    fields: {
                        sensorName: {
                            validators: {
                                notEmpty: {},
                                regexp: {
                                    regexp: /^[a-zA-Z0-9._\- ]+$/,
                                    message: 'Erlaubte Zeichen: a-Z, 0-9, _, -, .'
                                },
                                stringLength: {
                                    min: 1,
                                    max: 50,
                                    message: 'Name darf maximal 50 Zeichen lang sein'
                                }
                            }
                        },
                        location: {
                            validators: {
                                stringLength: {
                                    min: 0,
                                    max: 255,
                                    message: 'Standort darf maximal 255 Zeichen lang sein'
                                }
                            }
                        },
                        updateInterval: {
                            validators: {
                                between: {min: 1, max: 60, message: 'Das Intervall kann minimal 1 und maximal 60 Minuten betragen'}
                            }
                        }
                    }
                }).on('success.form.bv', function(e) {
                    e.preventDefault();
                    var $form = view.$el.find('div.addForm'),
                        $busy = view.$el.find('div.addBusy'),
                        $result = view.$el.find('div.addResult');
                    // Trigger animation to transition from the form to the busy display
                    $busy.removeClass('hide');
                    $form.one('transitionend', function() {
                        $form.addClass('hide');
                        $busy.css('position', 'static');
                        // Send model to server
                        HoneySens.data.models.sensors.add(view.model);
                        var name = view.$el.find('input[name="sensorName"]').val(),
                            location = view.$el.find('input[name="location"]').val(),
                            division = view.$el.find('select[name="division"]').val(),
                            updateInterval = view.$el.find('input[name="updateInterval"]').val() || null,
                            serverEndpointMode = view.$el.find('input[name="serverEndpoint"]:checked').val(),
                            serverHost = view.$el.find('input[name="serverHost"]').val(),
                            serverPortHTTPS = view.$el.find('input[name="serverPortHTTPS"]').val(),
                            firmwareRevision = view.$el.find('select[name="firmwareRevision"]').val(),
                            networkMode = view.$el.find('input[name="networkMode"]:checked').val(),
                            networkIP = view.$el.find('input[name="networkIP"]').val(),
                            networkNetmask = view.$el.find('input[name="networkNetmask"]').val(),
                            networkGateway = view.$el.find('input[name="networkGateway"]').val(),
                            networkDNS = view.$el.find('input[name="networkDNS"]').val(),
                            MACMode = view.$el.find('input[name="networkMACMode"]:checked').val(),
                            MACAddress = view.$el.find('input[name="customMAC"]').val(),
                            proxyMode = view.$el.find('input[name="proxyType"]:checked').val(),
                            proxyHost = view.$el.find('input[name="proxyHost"]').val(),
                            proxyPort = view.$el.find('input[name="proxyPort"]').val(),
                            proxyUser = view.$el.find('input[name="proxyUser"]').val(),
                            proxyPassword = view.$el.find('input[name="proxyPassword"]').val();
                        var modelData = {
                            name: name,
                            location: location,
                            division: division,
                            update_interval: updateInterval,
                            server_endpoint_mode: serverEndpointMode,
                            server_endpoint_host: serverHost,
                            server_endpoint_port_https: serverPortHTTPS,
                            firmware: firmwareRevision,
                            network_ip_mode: networkMode,
                            network_ip_address: networkIP,
                            network_ip_netmask: networkNetmask,
                            network_ip_gateway: networkGateway,
                            network_ip_dns: networkDNS,
                            network_mac_mode: MACMode,
                            network_mac_address: MACAddress,
                            proxy_mode: proxyMode,
                            proxy_host: proxyHost,
                            proxy_port: proxyPort,
                            proxy_user: proxyUser
                        };
                        if(proxyPassword.length > 0) modelData.proxy_password = proxyPassword;
                        // Reset password if no user was provided ('cause the server does the same)
                        if(proxyUser.length === 0) modelData.proxy_password = null;
                        view.model.save(modelData, {
                            success: function() {
                                // Render summary and firmware + config download view
                                $result.removeClass('hide');
                                $busy.one('transitionend', function() {
                                    $busy.addClass('hide');
                                    $result.css('position', 'static');
                                });
                                var overlayHeight = $('#overlay div.container-fluid').outerHeight(),
                                    contentHeight = $('#overlay div.container-fluid div.addBusy').outerHeight();
                                $busy.css('position', 'relative');
                                $busy.add($result).css('top', -Math.min(overlayHeight, contentHeight));
                            },
                            error: function() {
                                $result.removeClass('hide');
                                $result.find('div.resultSuccess').addClass('hide');
                                $result.find('div.resultError').removeClass('hide');
                                $busy.one('transitionend', function() {
                                    $busy.addClass('hide');
                                    $result.css('position', 'static');
                                });
                                var overlayHeight = $('#overlay div.container-fluid').outerHeight(),
                                    contentHeight = $('#overlay div.container-fluid div.addBusy').outerHeight();
                                $busy.css('position', 'relative');
                                $busy.add($result).css('top', -Math.min(overlayHeight, contentHeight));
                            }
                        });
                    });
                    var overlayHeight = $('#overlay div.container-fluid').outerHeight(),
                        contentHeight = $('#overlay div.container-fluid div.addForm').outerHeight();
                    $form.add($busy).css('top', -Math.min(overlayHeight, contentHeight));
                });
                // Set model data
                this.$el.find('select[name="division"] option[value="' + this.model.get('division') + '"]').prop('selected', true);
                // Preselecting bootstrap radio buttons is a bit more complicated...
                this.$el.find('input[name="serverEndpoint"][value="' + this.model.get('server_endpoint_mode') + '"]').prop('checked', true).parent().addClass('active');
                // Refresh again to set default value
                this.refreshServerEndpoint(this.model.get('server_endpoint_mode'), this.model.get('server_endpoint_host'), this.model.get('server_endpoint_port_https'));
                // Do the same for the remaining attributes
                this.$el.find('input[name="networkMode"][value="' + this.model.get('network_ip_mode') + '"]').prop('checked', true).parent().addClass('active');
                this.refreshNetworkMode(this.model.get('network_ip_mode'), this.model.get('network_ip_address'), this.model.get('network_ip_netmask'), this.model.get('network_ip_gateway'), this.model.get('network_ip_dns'));
                this.$el.find('input[name="networkMACMode"][value="' + this.model.get('network_mac_mode') + '"]').prop('checked', true).parent().addClass('active');
                this.refreshNetworkMAC(this.model.get('network_mac_mode'), this.model.get('network_mac_address'));
                this.$el.find('input[name="proxyType"][value="' + this.model.get('proxy_mode') + '"]').prop('checked', true).parent().addClass('active');
                this.refreshProxy(this.model.get('proxy_mode'), this.model.get('proxy_host'), this.model.get('proxy_port'), this.model.get('proxy_user'));
                var firmwarePreference = this.model.get('firmware') !== null ? 1 : 0;
                this.$el.find('input[name="firmwarePreference"][value="' + firmwarePreference + '"]').prop('checked', true).parent().addClass('active');
                this.refreshFirmwarePreference(firmwarePreference, this.model.getFirmware());
            },
            templateHelpers: {
                isNew: function() {
                    return !this.hasOwnProperty('id');
                },
                firmwareExists: function(platformId) {
                    if(platformId) {
                        return _.size(HoneySens.data.models.platforms.get(platformId).get('firmware_revisions')) > 0;
                    } else {
                        return HoneySens.data.models.platforms.byFirmwareAvailability().length > 0;
                    }
                }
            },
            serializeData: function() {
                var data = Marionette.ItemView.prototype.serializeData.apply(this, arguments);
                data.divisions = HoneySens.data.models.divisions.toJSON();
                // Only show platforms with attached default firmware to users
                data.platforms = _.map(HoneySens.data.models.platforms.filter(function(p) {
                    return p.get('default_firmware_revision') !== null;
                }), function(p) {
                    return p.toJSON();
                });
                return data;
            },
            /**
             * Render the firmware form based on the given mode and revision
             */
            refreshFirmwarePreference: function(mode, firmware) {
                var platform = null;
                if(firmware) platform = HoneySens.data.models.platforms.get(firmware.get('platform'));
                else platform = HoneySens.data.models.platforms.get(this.$el.find('div.firmwarePreferenceEnabled select[name="firmwarePlatform"]').val());
                switch(parseInt(mode)) {
                    case 0:
                        this.$el.find('div.firmwarePreferenceEnabled').addClass('hide');
                        this.$el.find('div.firmwarePreferenceDisabled').removeClass('hide');
                        // Reset the revision selector so that the form values can be read out correctly
                        this.$el.find('div.firmwarePreferenceEnabled select[name="firmwareRevision"]').val(null);
                        break;
                    case 1:
                        this.$el.find('div.firmwarePreferenceDisabled').addClass('hide');
                        this.$el.find('div.firmwarePreferenceEnabled').removeClass('hide');
                        this.$el.find('div.firmwarePreferenceEnabled select[name="firmwarePlatform"]').val(platform.id);
                        this.refreshFirmwareRevisionSelector(platform);
                        if(firmware) this.$el.find('div.firmwarePreferenceEnabled select[name="firmwareRevision"]').val(firmware.id);
                        break;
                }
            },
            /**
             * Render the revision selector based on the given platform
             */
            refreshFirmwareRevisionSelector: function(platform) {
                var revisions = platform.getFirmwareRevisions(),
                    result = '';
                revisions.forEach(function(r) {
                    result += '<option value="' + r.id + '">' + r.get('version') + '</option>';
                });
                this.$el.find('div.firmwarePreferenceEnabled select[name="firmwareRevision"]').html(result);
            },
            /**
             * Render the server endpoint form based on the given endpoint type and fills it with default values if provided
             */
            refreshServerEndpoint: function(endpoint, host, portHTTPS) {
                var networkMode = this.$el.find('input[name="networkMode"]:checked').val(),
                    MACMode = this.$el.find('input[name="networkMACMode"]:checked').val(),
                    proxyMode = this.$el.find('input[name="proxyType"]:checked').val();
                endpoint = parseInt(endpoint);
                host = host || null;
                portHTTPS = portHTTPS || null;
                var $host = this.$el.find('input[name="serverHost"]'),
                    $portHTTPS = this.$el.find('input[name="serverPortHTTPS"]');
                this.refreshValidators(endpoint, networkMode, MACMode, proxyMode);
                switch(endpoint) {
                    case 0:
                        $host.attr('disabled', 'disabled').val(HoneySens.data.settings.get('serverHost'));
                        $portHTTPS.attr('disabled', 'disabled').val(HoneySens.data.settings.get('serverPortHTTPS'));
                        break;
                    case 1:
                        $host.attr('disabled', false).val(host);
                        $portHTTPS.attr('disabled', false).val(portHTTPS);
                        break;
                }
            },
            /**
             * Render the IPv4 configuration form based on the given mode. Also set default values, if given.
             */
            refreshNetworkMode: function(mode, ip, netmask, gateway, dns) {
                var serverMode = this.$el.find('input[name="serverEndpoint"]:checked').val(),
                    MACMode = this.$el.find('input[name="networkMACMode"]:checked').val(),
                    proxyMode = this.$el.find('input[name="proxyType"]:checked').val();
                mode = parseInt(mode);
                ip = ip || null;
                netmask = netmask || null;
                gateway = gateway || null;
                dns = dns || null;
                switch(mode) {
                    case 0:
                        this.$el.find('div.networkModeStatic').addClass('hide');
                        this.$el.find('div.networkModeNone').addClass('hide');
                        this.$el.find('div.networkModeDHCP').removeClass('hide');
                        break;
                    case 1:
                        this.$el.find('div.networkModeDHCP').addClass('hide');
                        this.$el.find('div.networkModeNone').addClass('hide');
                        this.$el.find('div.networkModeStatic').removeClass('hide');
                        this.$el.find('div.networkModeStatic input[name="networkIP"]').val(ip);
                        this.$el.find('div.networkModeStatic input[name="networkNetmask"]').val(netmask);
                        this.$el.find('div.networkModeStatic input[name="networkGateway"]').val(gateway);
                        this.$el.find('div.networkModeStatic input[name="networkDNS"]').val(dns);
                        break;
                    case 2:
                        this.$el.find('div.networkModeStatic').addClass('hide');
                        this.$el.find('div.networkModeDHCP').addClass('hide');
                        this.$el.find('div.networkModeNone').removeClass('hide');
                        break;
                }
                this.refreshValidators(serverMode, mode, MACMode, proxyMode);
            },
            /**
             * Render the custom MAC form based on the given mode. Also set the mac, if given.
             */
            refreshNetworkMAC: function(mode, mac) {
                var serverMode = this.$el.find('input[name="serverEndpoint"]:checked').val(),
                    networkMode = this.$el.find('input[name="networkMode"]:checked').val(),
                    proxyMode = this.$el.find('input[name="proxyType"]:checked').val();
                mode = parseInt(mode);
                mac = mac || null;
                switch(mode) {
                    case 0:
                        this.$el.find('div.networkMACCustom').addClass('hide');
                        this.$el.find('div.networkMACOriginal').removeClass('hide');
                        break;
                    case 1:
                        this.$el.find('div.networkMACOriginal').addClass('hide');
                        this.$el.find('div.networkMACCustom').removeClass('hide');
                        this.$el.find('div.networkMACCustom input[name="customMAC"]').val(mac);
                        break;
                }
                this.refreshValidators(serverMode, networkMode, mode, proxyMode);
            },
            refreshProxy: function(mode, host, port, user) {
                var serverMode = this.$el.find('input[name="serverEndpoint"]:checked').val(),
                    networkMode = this.$el.find('input[name="networkMode"]:checked').val(),
                    MACMode = this.$el.find('input[name="networkMACMode"]:checked').val();
                mode = parseInt(mode);
                host = host || null;
                port = port || null;
                user = user || null;
                switch(mode) {
                    case 0:
                        this.$el.find('div.proxyTypeEnabled').addClass('hide');
                        this.$el.find('div.proxyTypeDisabled').removeClass('hide');
                        break;
                    case 1:
                        this.$el.find('div.proxyTypeDisabled').addClass('hide');
                        this.$el.find('div.proxyTypeEnabled').removeClass('hide');
                        this.$el.find('div.proxyTypeEnabled input[name="proxyHost"]').val(host);
                        this.$el.find('div.proxyTypeEnabled input[name="proxyPort"]').val(port);
                        this.$el.find('div.proxyTypeEnabled input[name="proxyUser"]').val(user);
                        this.$el.find('div.proxyTypeEnabled input[name="proxyPassword"]').val(null);
                        break;
                }
                this.refreshValidators(serverMode, networkMode, MACMode, mode);
            },
            refreshValidators: function(serverMode, networkMode, MACMode, proxyMode) {
                var $form = this.$el.find('form');
                // reset form, remove all volatile fields
                $form.data('bootstrapValidator').resetForm();
                $form.bootstrapValidator('removeField', 'serverHost');
                $form.bootstrapValidator('removeField', 'serverPortHTTPS');
                $form.bootstrapValidator('removeField', 'networkIP');
                $form.bootstrapValidator('removeField', 'networkNetmask');
                $form.bootstrapValidator('removeField', 'networkGateway');
                $form.bootstrapValidator('removeField', 'networkDNS');
                $form.bootstrapValidator('removeField', 'customMAC');
                $form.bootstrapValidator('removeField', 'proxyHost');
                $form.bootstrapValidator('removeField', 'proxyPort');
                switch(parseInt(serverMode)) {
                    case 0:
                        break;
                    case 1:
                        $form.bootstrapValidator('addField', this.$el.find('input[name="serverHost"]'), this.validators.networkIP);
                        $form.bootstrapValidator('addField', this.$el.find('input[name="serverPortHTTPS"]'), this.validators.PortNumber);
                        break;
                }
                switch(parseInt(networkMode)) {
                    case 0:
                        break;
                    case 1:
                        $form.bootstrapValidator('addField', this.$el.find('input[name="networkIP"]'), this.validators.networkIP);
                        $form.bootstrapValidator('addField', this.$el.find('input[name="networkNetmask"]'), this.validators.networkIP);
                        $form.bootstrapValidator('addField', this.$el.find('input[name="networkGateway"]'), this.validators.networkIPOptional);
                        $form.bootstrapValidator('addField', this.$el.find('input[name="networkDNS"]'), this.validators.networkIPOptional);
                        break;
                }
                switch(parseInt(MACMode)) {
                    case 0:
                        break;
                    case 1:
                        $form.bootstrapValidator('addField', this.$el.find('input[name="customMAC"]'), this.validators.MacAddress);
                        break;
                }
                switch(parseInt(proxyMode)) {
                    case 0:
                        break;
                    case 1:
                        $form.bootstrapValidator('addField', this.$el.find('input[name="proxyHost"]'), this.validators.notEmpty);
                        $form.bootstrapValidator('addField', this.$el.find('input[name="proxyPort"]'), this.validators.PortNumber);
                        break;
                }
            }
        });
    });

    return HoneySens.Sensors.Views.SensorEdit;
});
