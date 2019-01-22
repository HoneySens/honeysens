define(['app/app',
        'app/models',
        'tpl!app/modules/events/templates/EventDetails.tpl',
        'tpl!app/modules/events/templates/DetailsDataItem.tpl',
        'tpl!app/modules/events/templates/DetailsDataList.tpl',
        'tpl!app/modules/events/templates/DetailsInteractionItem.tpl',
        'tpl!app/modules/events/templates/DetailsInteractionList.tpl',
        'tpl!app/modules/events/templates/DetailsPacketList.tpl',
        'tpl!app/modules/events/templates/DetailsPacketListItem.tpl',
        'app/views/common'],
function(HoneySens, Models, EventDetailsTpl, DetailsDataItemTpl, DetailsDataListTpl, DetailsInteractionItemTpl,
         DetailsInteractionListTpl, DetailsPacketListTpl, DetailsPacketListItemTpl) {
    HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {

        var showTimestampHelper = function() {
            var ts = this.timestamp;
            return (('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2));
        };

        var dataItemView = Marionette.ItemView.extend({
            template: DetailsDataItemTpl,
            tagName: 'tr',
            templateHelpers: {
                showType: function() {
                    switch(this.type) {
                        case Models.EventDetail.type.GENERIC:
                            return 'Sonstiges';
                            break;
                        default:
                            return 'Unbekannt';
                    }
                }
            }
        });

        var dataListView = Marionette.CompositeView.extend({
            template: DetailsDataListTpl,
            className: 'panel panel-primary',
            childViewContainer: 'tbody',
            childView: dataItemView
        });

        var interactionItemView = Marionette.ItemView.extend({
            template: DetailsInteractionItemTpl,
            tagName: 'tr',
            templateHelpers: {
                showTimestamp: showTimestampHelper
            }
        });

        var interactionListView = Marionette.CompositeView.extend({
            template: DetailsInteractionListTpl,
            className: 'panel panel-primary',
            childViewContainer: 'tbody',
            childView: interactionItemView,
            templateHelpers: {
                showModelCount: function() {
                    return this.collection.length;
                }
            },
            serializeData: function() {
                var data = Marionette.CompositeView.prototype.serializeData.apply(this, arguments);
                data.collection = this.collection;
                return data;
            }
        });

        var packetListItemView = Marionette.ItemView.extend({
            template: DetailsPacketListItemTpl,
            tagName: 'tr',
            templateHelpers: {
                showTimestamp: showTimestampHelper,
                showProtocol: function() {
                    switch(this.protocol) {
                        case Models.EventPacket.protocol.UNKNOWN:
                            return 'Unbekannt';
                            break;
                        case Models.EventPacket.protocol.TCP:
                            return 'TCP';
                            break;
                        case Models.EventPacket.protocol.UDP:
                            return 'UDP';
                            break;
                    }
                },
                showPayload: function() {
                    if(this.payload) {
                        return atob(this.payload)
                            .replace(/\n/g, "\\n")
                            .replace(/\t/g, "\\t");
                    }
                },
                showFlags: function() {
                    if(this.headers) {
                        flags = JSON.parse(this.headers)[0].flags;
                        flagString = '';
                        if((flags & parseInt(1, 2)) > 0) flagString += 'F';
                        if((flags & parseInt(10, 2)) > 0) flagString += 'S';
                        if((flags & parseInt(100, 2)) > 0) flagString += 'R';
                        if((flags & parseInt(1000, 2)) > 0) flagString += 'P';
                        if((flags & parseInt(10000, 2)) > 0) flagString += 'A';
                        if((flags & parseInt(100000, 2)) > 0) flagString += 'U';
                        return flagString;
                    }
                }
            }
        });

        var packetListView = Marionette.CompositeView.extend({
            template: DetailsPacketListTpl,
            className: 'panel panel-primary',
            childViewContainer: 'tbody',
            childView: packetListItemView,
            templateHelpers: {
                showModelCount: function() {
                    return this.collection.length;
                }
            },
            serializeData: function() {
                var data = Marionette.CompositeView.prototype.serializeData.apply(this, arguments);
                data.collection = this.collection;
                return data;
            }
        });

        Views.EventDetails = Marionette.LayoutView.extend({
            template: EventDetailsTpl,
            className: 'container-fluid',
            regions: {
                dataList: 'div.detailsDataList',
                interactionList: 'div.detailsInteractionList',
                packetList: 'div.packetList'
            },
            events: {
                'click button.btn-default': function() {
                    HoneySens.request('view:content').overlay.empty();
                }
            },
            templateHelpers: HoneySens.Views.EventTemplateHelpers,
            initialize: function() {
                this.eventDetails = this.model.getDetailsAndPackets();
                // bind to the details collection, because we split that one into data details and interaction details further below
                this.listenTo(this.eventDetails.details, 'reset', this.render);
                // re-render on packet changes, because the visibility of the whole packet list might change when the first packet is added
                this.listenTo(this.eventDetails.packets, 'reset', this.render);
            },
            onRender: function() {
                var dataDetails = new Models.EventDetails(this.eventDetails.details.filter(function(m) {
                    return m.get('type') === Models.EventDetail.type.GENERIC;
                }));
                var interactionDetails = new Models.EventDetails(this.eventDetails.details.filter(function(m) {
                    return m.get('type') === Models.EventDetail.type.INTERACTION;
                }));
                if(dataDetails.length > 0) this.dataList.show(new dataListView({collection: dataDetails}));
                if(interactionDetails.length > 0) this.interactionList.show(new interactionListView({collection: interactionDetails}));
                if(this.eventDetails.packets.length > 0) this.packetList.show(new packetListView({collection: this.eventDetails.packets}));
            }
        });
    });

    return HoneySens.Events.Views.EventDetails;
});