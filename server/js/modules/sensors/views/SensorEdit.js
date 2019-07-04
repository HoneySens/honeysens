define(['app/app',
        'app/models',
        'tpl!app/modules/sensors/templates/SensorEdit.tpl',
        'app/views/common',
        'validator'],
function(HoneySens, Models, SensorEditTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.SensorEdit = Marionette.ItemView.extend({
            template: SensorEditTpl,
            className: 'container-fluid',
            cfgTaskId: null, // The id of a sensor config creation task we're waiting for, if any
            events: {
                'click button.cancel': function() {
                    HoneySens.request('view:content').overlay.empty();
                },
                'click button.useCustomUpdateInterval': function(e) {
                    var $updateIntervalField = this.$el.find('input[name="updateInterval"]'),
                        $trigger = this.$el.find('button.useCustomUpdateInterval'),
                        customUpdateInterval = !$trigger.hasClass('active');

                    $updateIntervalField.prop('disabled', !customUpdateInterval);
                    $updateIntervalField.prop('required', customUpdateInterval);
                    if(customUpdateInterval) {
                        $trigger.addClass('active');
                        $updateIntervalField.val(this.model.get('update_interval'));
                    } else {
                        $trigger.removeClass('active');
                        $updateIntervalField.val(HoneySens.data.settings.get('sensorsUpdateInterval'));
                    }
                    this.$el.find('form').validator('update');
                },
                'click button.useCustomServiceNetwork': function(e) {
                    var $updateServiceNetworkField = this.$el.find('input[name="serviceNetwork"]'),
                        $trigger = this.$el.find('button.useCustomServiceNetwork'),
                        customServiceNetwork = !$trigger.hasClass('active');

                    $updateServiceNetworkField.prop('disabled', !customServiceNetwork);
                    $updateServiceNetworkField.prop('required', customServiceNetwork);
                    if(customServiceNetwork) {
                        $trigger.addClass('active');
                        $updateServiceNetworkField.val(this.model.get('service_network'));
                    } else {
                        $trigger.removeClass('active');
                        $updateServiceNetworkField.val(HoneySens.data.settings.get('sensorsServiceNetwork'));
                    }
                    this.$el.find('form').validator('update');
                },
                'click button.useCustomServerEndpoint': function (e) {
                    var $updateServerHostField = this.$el.find('input[name="serverHost"]'),
                        $updateServerPortField = this.$el.find('input[name="serverPortHTTPS"]'),
                        $trigger = this.$el.find('button.useCustomServerEndpoint'),
                        customServerEndpoint = !$trigger.hasClass('active');

                        $updateServerHostField.prop('disabled', !customServerEndpoint);
                        $updateServerPortField.prop('disabled', !customServerEndpoint);
                        $updateServerHostField.prop('required', customServerEndpoint);
                        $updateServerPortField.prop('required', customServerEndpoint);

                        if(customServerEndpoint) {
                            $trigger.addClass('active');
                            $updateServerHostField.val(this.model.get('server_endpoint_host'));
                            $updateServerPortField.val(this.model.get('server_endpoint_port_https'));
                        } else {
                            $trigger.removeClass('active');
                            $updateServerHostField.val(HoneySens.data.settings.get('serverHost'));
                            $updateServerPortField.val(HoneySens.data.settings.get('serverPortHTTPS'));
                        }
                    this.$el.find('form').validator('update');
                },
                'change input[name="firmwarePreference"]': function(e) {
                    this.refreshFirmwarePreference(e.target.value);
                },
                'change select[name="firmwarePlatform"]': function(e) {
                    this.refreshFirmwareRevisionSelector(HoneySens.data.models.platforms.get(e.target.value));
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
                    var valid = true;
                    this.$el.find('form').validator('validate');
                    this.$el.find('form .form-group').each(function() {
                        valid = !$(this).hasClass('has-error') && valid;
                    });

                    if(valid) {
                        this.$el.find('form').trigger('submit');
                    }
                },
                'click button.reqConfig': function() {
                    var view = this;
                    // User feedback
                    this.$el.find('div.configArchive h5').removeClass('hide');
                    this.$el.find('div.configArchive button').addClass('hide');
                    // Initiate config generation
                    $.ajax({
                        type: 'GET',
                        url: 'api/sensors/config/' + this.model.id,
                        dataType: 'json',
                        success: function(resp, code, xhr) {
                            HoneySens.Views.waitForTask(new Models.Task(xhr.responseJSON), {
                                done: function(task) {
                                    task.downloadResult(true);
                                    // Display download button again
                                    view.$el.find('div.configArchive h5').addClass('hide');
                                    view.$el.find('div.configArchive button').removeClass('hide');
                                },
                                error: function(task, resp) {
                                    view.$el.find('div.alert-danger').removeClass('hide');
                                    view.$el.find('div.configArchive h5').addClass('hide');
                                }
                            });
                        },
                        error: function() {
                            view.$el.find('div.alert-danger').removeClass('hide');
                            view.$el.find('div.configArchive h5').addClass('hide');
                        }
                    });
                },
                'click label.disabled': function() {
                    // Ignore clicks on disabled labels. This fixes a bootstrap bug.
                    // See: https://github.com/twbs/bootstrap/issues/16703
                    return false;
                }
            },
            onRender: function() {
                var view = this;
                // Enable help popovers
                this.$el.find('[data-toggle="popover"]').popover();
                // Busy view spinner
                this.$el.find('div.loading').html(HoneySens.Views.spinner.spin().el);

                this.$el.find('form').validator().on('submit', function (e) {
                    if (!e.isDefaultPrevented()) {
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
                                updateInterval = view.$el.find('button.useCustomUpdateInterval').hasClass('active') ? view.$el.find('input[name="updateInterval"]').val() : null,
                                serviceNetwork = view.$el.find('button.useCustomServiceNetwork').hasClass('active') ? view.$el.find('input[name="serviceNetwork"]').val() : null,
                                serverEndpointMode = view.$el.find('button.useCustomServerEndpoint').hasClass('active') ? '1' : '0',
                                serverHost = view.$el.find('button.useCustomServerEndpoint').hasClass('active') ? view.$el.find('input[name="serverHost"]').val() : null,
                                serverPortHTTPS = view.$el.find('button.useCustomServerEndpoint').hasClass('active') ? view.$el.find('input[name="serverPortHTTPS"]').val() : null,
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
                                service_network: serviceNetwork,
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
                    }
                });


                // Set model data
                this.$el.find('select[name="division"] option[value="' + this.model.get('division') + '"]').prop('selected', true);
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
                },
                hasCustomUpdateInterval: function() {
                    return this.update_interval > 0;
                },
                hasCustomServiceNetwork: function() {
                    return this.service_network;
                },
                hasCustomServerHost: function() {
                    return this.server_endpoint_host;
                },
                hasCustomServerPort: function() {
                    return this.server_endpoint_port_https > 0;
                },
                getUpdateInterval: function() {
                    if(this.update_interval > 0) return this.update_interval;
                    else return HoneySens.data.settings.get('sensorsUpdateInterval');
                },
                getServiceNetwork: function() {
                    if(this.service_network) return this.service_network;
                    else return HoneySens.data.settings.get('sensorsServiceNetwork');
                },
                getServerHost: function() {
                    if (this.server_endpoint_host) return this.server_endpoint_host;
                    else return HoneySens.data.settings.get('serverHost');
                },
                getServerPortHTTPS: function() {
                    if (this.server_endpoint_port_https) return this.server_endpoint_port_https;
                    else return HoneySens.data.settings.get('serverPortHTTPS');
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
             * Render the IPv4 configuration form based on the given mode. Also set default values, if given.
             */
            refreshNetworkMode: function(mode, ip, netmask, gateway, dns) {
                var MACMode = this.$el.find('input[name="networkMACMode"]:checked').val(),
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
                this.refreshValidators(mode, MACMode, proxyMode);
            },
            /**
             * Render the custom MAC form based on the given mode. Also set the mac, if given.
             */
            refreshNetworkMAC: function(mode, mac) {
                var networkMode = this.$el.find('input[name="networkMode"]:checked').val(),
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
                this.refreshValidators(networkMode, mode, proxyMode);
            },
            refreshProxy: function(mode, host, port, user) {
                var networkMode = this.$el.find('input[name="networkMode"]:checked').val(),
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
                this.refreshValidators(networkMode, MACMode, mode);
            },
            refreshValidators: function(networkMode, MACMode, proxyMode) {
                var $form = this.$el.find('form'),
                    serverMode = this.$el.find('button.useCustomServerEndpoint').hasClass('active') ? '1' : '0';
                // reset form, remove all volatile fields
                $form.validator('destroy');

                $form.find('input[name="serverHost"]').attr('required', false);
                $form.find('input[name="serverPortHTTPS"]').attr('required', false);
                $form.find('input[name="networkIP"]').attr('required', false);
                $form.find('input[name="networkNetmask"]').attr('required', false);
                $form.find('input[name="customMAC"]').attr('required', false);
                $form.find('input[name="proxyHost"]').attr('required', false);
                $form.find('input[name="proxyPort"]').attr('required', false);

                switch(parseInt(serverMode)) {
                    case 0:
                        break;
                    case 1:
                        this.$el.find('input[name="serverHost"]').attr('required', true);
                        this.$el.find('input[name="serverPortHTTPS"]').attr('required', true);
                        break;
                }
                switch(parseInt(networkMode)) {
                    case 0:
                        break;
                    case 1:
                        this.$el.find('input[name="networkIP"]').attr('required', true);
                        this.$el.find('input[name="networkNetmask"]').attr('required', true);
                        break;
                }
                switch(parseInt(MACMode)) {
                    case 0:
                        break;
                    case 1:
                        this.$el.find('input[name="customMAC"]').attr('required', true);
                        break;
                }
                switch(parseInt(proxyMode)) {
                    case 0:
                        break;
                    case 1:
                        this.$el.find('input[name="proxyHost"]').attr('required', true);
                        this.$el.find('input[name="proxyPort"]').attr('required', true);
                        break;
                }

                $form.validator('update');
            }
        });
    });

    return HoneySens.Sensors.Views.SensorEdit;
});