define(['app/app', 'app/models',
        'app/modules/sensors/views/ModalCert',
        'app/modules/sensors/views/ModalSensorStatusList',
        'app/modules/sensors/views/ModalSensorAdd',
        'app/modules/sensors/views/ModalSensorRemove',
        'tpl!app/modules/sensors/templates/SensorItem.tpl'],
function(HoneySens, Models, ModalCertView, ModalSensorStatusListView, ModalSensorAddView, ModalSensorRemove, SensorItemTpl) {
    HoneySens.module('Sensors.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.SensorItem = Marionette.ItemView.extend({
            template: SensorItemTpl,
            tagName: 'tr',
            events: {
                'click button.removeSensor': function(e) {
                    e.preventDefault();
                    HoneySens.request('sensors:remove', this.model);
                },
                'click button.editSensor': function(e) {
                    e.preventDefault();
                    HoneySens.request('sensors:edit', this.model);
                },
                'click button.showCert': function(e) {
                    e.preventDefault();
                    var cert = new Models.SSLCert({ id: this.model.get('cert') });
                    cert = HoneySens.data.models.certs.add(cert);
                    HoneySens.request('view:modal').show(new ModalCertView({ model: cert }));
                },
                'click button.addCert': function(e) {
                    e.preventDefault();
                    HoneySens.request('view:modal').show(new ModalSensorAddView({ model: this.model }));
                },
                'click button.showStatus': function(e) {
                    e.preventDefault();
                    var collection = this.model.status;
                    collection.fetch({ reset: true });
                    HoneySens.request('view:modal').show(new ModalSensorStatusListView({ collection: collection }));
                }
            },
            modelEvents: {
                'change': 'render'
            },
            onRender: function() {
                if(this.model.get('cert') == '') {
                    this.$el.find('td.showCert').addClass('danger').html('<button type="button" class="addCert btn btn-default btn-xs"><span class="glyphicon glyphicon-warning-sign"></span> Nicht vorhanden</button>');
                }
                this.$el.find('button').tooltip();
            },
            templateHelpers: {
                isTimedOut: function() {
                    var now = new Date().getTime() / 1000;
                    return ((now - this.last_status_ts) > ((this.update_interval * 60) + 60)); // 1 minute timeout tolerance
                },
                showLastStatusTS: function() {
                    var now = new Date().getTime() / 1000,
                        diffMin = Math.floor((now - this.last_status_ts) / 60);
                    if(diffMin < 60) {
                        return "vor " + diffMin + " Minuten";
                    } else if(diffMin < (60 * 24)) {
                        return "vor " + Math.floor(diffMin / 60) + " Stunden";
                    } else if(diffMin >= (60 * 24)) {
                        return "vor " + Math.floor(diffMin / (60 * 24)) + " Tag(en)";
                    }
                }
            }
        });
    });

    return HoneySens.Sensors.Views.SensorItem;
});