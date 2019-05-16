define(['app/app', 'app/models', 'spin'],
function(HoneySens, Models, Spinner) {
    HoneySens.module('Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
        Views.EventTemplateHelpers = {
            showTimestamp: function (ts) {
                var ts = ts || this.timestamp;
                ts = new Date(ts * 1000);
                return ('0' + ts.getDate()).slice(-2) + '.' + ('0' + (ts.getMonth() + 1)).slice(-2) + '.' +
                    ts.getFullYear() + ' ' + ('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2);
            },
            showClassification: function (classification) {
                var classification = classification || this.classification;
                switch (classification) {
                    case Models.Event.classification.UNKNOWN:
                        return 'Unbekannt';
                        break;
                    case Models.Event.classification.ICMP:
                        return 'ICMP';
                        break;
                    case Models.Event.classification.CONN_ATTEMPT:
                        return 'Verbindungsversuch';
                        break;
                    case Models.Event.classification.LOW_HP:
                        return 'Honeypot';
                        break;
                    case Models.Event.classification.PORTSCAN:
                        return 'Portscan';
                        break;
                    default:
                        return 'Ungültige Klassifikation';
                }
            },
            showSensor: function (sensor) {
                var sensor = sensor || this.sensor;
                return HoneySens.data.models.sensors.get(sensor).get('name');
            },
            showSummary: function (summary, numberOfPackets, numberOfDetails) {
                var summary = summary || this.summary;
                var interactionCount;
                interactionCount = parseInt(numberOfPackets) + parseInt(numberOfDetails);
                summary = summary + ' (' + interactionCount + ')';
                return summary;
            }
        };

        // Creates a menu from the global application menu data (and therefore from all submodules)
        Views.createMenu = function(items) {
            var result = '',
                view = this;
            $.each(items, function() {
                var subitems = '';
                if(this.hasOwnProperty('items')) {
                    subitems = '<ul class="nav">' + view.createMenu(this.items) + '</ul>';
                }
                if(HoneySens.assureAllowed(this.permission.domain, this.permission.action)) {
                    result += '<li><a href="#' + this.uri + '"><span class="' + this.iconClass + '"></span><span class="menuLabel">&nbsp;&nbsp;' + this.title + '</span></a>' + subitems + '</li>';
                }
            });
            return result;
        };

        // Initialize spinner views
        Views.spinner = new Spinner({lines: 13, length: 4, width: 2, radius: 6, corners: 1, rotate: 0, direction: 1, color: '#000',
            speed: 1, trail: 60, shadow: false, hwaccel: false, className: 'spinner', zIndex: 2e9, top: '50%', left: '50%'});
        Views.inlineSpinner = new Spinner({lines: 10, length: 3, width: 2, radius: 4, corners: 1, rotate: 0, direction: 1, color: '#000',
            speed: 1, trail: 60, shadow: false, hwaccel: false, className: 'spinner', zIndex: 2e9, top: '50%', left: '50%'});

        // generic animation methods, to be reused within animated views
        function animateIn(options) {
            var v = this;
            options = typeof options === 'object' ? options : {};
            if('animation' in options) {
                switch (options.animation) {
                    case 'slideLeft':
                        v.$el.css({ right: 0, left: function() { return $(this).parents('div.transitionContainer').outerWidth(); },
                            width: function() { return $(this).parents('div.transitionContainer').outerWidth(); }});
                        v.$el.animate({left: 0}, {
                            duration: 400, complete: function () {
                                v.$el.css('width', 'auto');
                                _.bind(v.trigger, v, 'animateIn');
                                v.trigger('animateIn');
                            }
                        });
                        break;
                    case 'slideRight':
                        v.$el.css({ left: function() { return -$(this).parents('div.transitionContainer').outerWidth(); },
                            width: function() { return $(this).parents('div.transitionContainer').outerWidth(); }});
                        this.$el.animate({left: 0}, {
                            duration: 400, complete: function () {
                                v.$el.css('width', 'auto');
                                _.bind(v.trigger, v, 'animateIn');
                                v.trigger('animateIn');
                            }
                        });
                        break;
                }
            } else {
                _.bind(v.trigger, v, 'animateIn');
                v.trigger('animateIn');
            }
        }

        function animateOut(options) {
            var v = this, mainWidth = this.$el.parents('div.transitionContainer').outerWidth();
            options = typeof options === 'object' ? options : {};
            if('animation' in options) {
                switch(options.animation) {
                    case 'slideLeft':
                        v.$el.css('width', mainWidth).css('right', 'auto').css('left', 'auto');
                        v.$el.animate({ left: -mainWidth }, { duration: 400, complete: function() {
                            _.bind(v.trigger, v, 'animateOut');
                            v.trigger('animateOut');
                        }});
                        break;
                    case 'slideRight':
                        v.$el.css('width', mainWidth).css('right', 'auto').css('left', 'auto');
                        v.$el.animate({ left: mainWidth}, { duration: 400, complete: function() {
                            _.bind(v.trigger, v, 'animateOut');
                            v.trigger('animateOut');
                        }});
                        break
                }
            } else {
                _.bind(v.trigger, v, 'animateOut');
                v.trigger('animateOut');

            }
        }

        // based on https://github.com/jmeas/marionette.transition-region
        Views.SlideCompositeView = Marionette.CompositeView.extend({
            className: 'transitionView',
            transitionInCss: {},
            animateIn: animateIn,
            animateOut: animateOut
        });

        Views.SlideItemView = Marionette.ItemView.extend({
            className: 'transitionView',
            transitionInCss: {},
            animateIn: animateIn,
            animateOut: animateOut
        });

        Views.SlideLayoutView = Marionette.LayoutView.extend({
            className: 'transitionView',
            transitionInCss: {},
            animateIn: animateIn,
            animateOut: animateOut
        });
    });
});
