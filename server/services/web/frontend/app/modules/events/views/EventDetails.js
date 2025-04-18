import HoneySens from 'app/app';
import Models from 'app/models';
import EventDetailsTpl from 'app/modules/events/templates/EventDetails.tpl';
import DetailsDataItemTpl from 'app/modules/events/templates/DetailsDataItem.tpl';
import DetailsDataListTpl from 'app/modules/events/templates/DetailsDataList.tpl';
import DetailsInteractionItemTpl from 'app/modules/events/templates/DetailsInteractionItem.tpl';
import DetailsInteractionListTpl from 'app/modules/events/templates/DetailsInteractionList.tpl';
import DetailsPacketListTpl from 'app/modules/events/templates/DetailsPacketList.tpl';
import DetailsPacketListItemTpl from 'app/modules/events/templates/DetailsPacketListItem.tpl';
import 'app/views/common';

HoneySens.module('Events.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {

    var showTimestampHelper = function() {
        var ts = this.timestamp;
        return (('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2));
    };

    var dataItemView = Marionette.ItemView.extend({
        template: _.template(DetailsDataItemTpl),
        tagName: 'tr',
        templateHelpers: {
            showType: function() {
                switch(this.type) {
                    case Models.EventDetail.type.GENERIC:
                        return _.t('events:eventDetailGeneric');
                        break;
                    default:
                        return _.t('unknown');
                }
            }
        }
    });

    var dataListView = Marionette.CompositeView.extend({
        template: _.template(DetailsDataListTpl),
        className: 'panel panel-primary',
        childViewContainer: 'tbody',
        childView: dataItemView
    });

    var interactionItemView = Marionette.ItemView.extend({
        template: _.template(DetailsInteractionItemTpl),
        tagName: 'tr',
        templateHelpers: {
            showTimestamp: showTimestampHelper
        }
    });

    var interactionListView = Marionette.CompositeView.extend({
        template: _.template(DetailsInteractionListTpl),
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
        template: _.template(DetailsPacketListItemTpl),
        tagName: 'tr',
        templateHelpers: {
            showTimestamp: showTimestampHelper,
            showProtocol: function() {
                switch(this.protocol) {
                    case Models.EventPacket.protocol.UNKNOWN:
                        return _.t("unknown");
                        break;
                    case Models.EventPacket.protocol.TCP:
                        return _.t("tcp");
                        break;
                    case Models.EventPacket.protocol.UDP:
                        return _.t("udp");
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
                    var flags = JSON.parse(this.headers)[0].flags;
                    var flagString = '';
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
        template: _.template(DetailsPacketListTpl),
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
        template: _.template(EventDetailsTpl),
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
            this.listenTo(this.eventDetails.details, 'reset', this.updateDetails);
            // re-render on packet changes, because the visibility of the whole packet list might change when the first packet is added
            this.listenTo(this.eventDetails.packets, 'reset', this.updateDetails);
        },
        updateDetails: function() {
            var dataDetails = new Models.EventDetails(this.eventDetails.details.filter(function(m) {
                return m.get('type') === Models.EventDetail.type.GENERIC;
            }));
            var interactionDetails = new Models.EventDetails(this.eventDetails.details.filter(function(m) {
                return m.get('type') === Models.EventDetail.type.INTERACTION;
            }));
            if(dataDetails.length > 0) this.getRegion('dataList').show(new dataListView({collection: dataDetails}));
            if(interactionDetails.length > 0) this.getRegion('interactionList').show(new interactionListView({collection: interactionDetails}));
            if(this.eventDetails.packets.length > 0) this.getRegion('packetList').show(new packetListView({collection: this.eventDetails.packets}));
        }
    });
});

export default HoneySens.Events.Views.EventDetails;