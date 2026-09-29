import i18n from 'app/common/i18n';
import { View, CollectionView } from 'backbone.marionette';
import { radio } from 'app/radio';
import { EventDetails, EventDetailType, EventPacketProtocol } from 'app/models';
import EventDetailsTpl from 'app/modules/events/templates/EventDetails.tpl';
import DetailsDataItemTpl from 'app/modules/events/templates/DetailsDataItem.tpl';
import DetailsDataListTpl from 'app/modules/events/templates/DetailsDataList.tpl';
import DetailsInteractionItemTpl from 'app/modules/events/templates/DetailsInteractionItem.tpl';
import DetailsInteractionListTpl from 'app/modules/events/templates/DetailsInteractionList.tpl';
import DetailsPacketListTpl from 'app/modules/events/templates/DetailsPacketList.tpl';
import DetailsPacketListItemTpl from 'app/modules/events/templates/DetailsPacketListItem.tpl';
import { EventTemplateHelpers } from 'app/views/common';


var showTimestampHelper = function() {
    var ts = this.timestamp;
    return (('0' + ts.getHours()).slice(-2) + ':' + ('0' + ts.getMinutes()).slice(-2) + ':' + ('0' + ts.getSeconds()).slice(-2));
};

var dataItemView = View.extend({
    template: _.template(DetailsDataItemTpl),
    tagName: 'tr',
    templateContext: {
        showType: function() {
            switch(this.type) {
                case EventDetailType.GENERIC:
                    return i18n.t('events:eventDetailGeneric');
                    break;
                default:
                    return i18n.t('unknown');
            }
        }
    }
});

var dataListView = CollectionView.extend({
    template: _.template(DetailsDataListTpl),
    templateContext: {...i18n},
    className: 'panel panel-primary',
    childViewContainer: 'tbody',
    childView: dataItemView
});

var interactionItemView = View.extend({
    template: _.template(DetailsInteractionItemTpl),
    tagName: 'tr',
    templateContext: {
        showTimestamp: showTimestampHelper
    }
});

var interactionListView = CollectionView.extend({
    template: _.template(DetailsInteractionListTpl),
    className: 'panel panel-primary',
    childViewContainer: 'tbody',
    childView: interactionItemView,
    templateContext: {
        ...i18n,
        showModelCount: function() {
            return this.collection.length;
        }
    },
    serializeData: function() {
        var data = CollectionView.prototype.serializeData.apply(this, arguments);
        data.collection = this.collection;
        return data;
    }
});

var packetListItemView = View.extend({
    template: _.template(DetailsPacketListItemTpl),
    tagName: 'tr',
    templateContext: {
        showTimestamp: showTimestampHelper,
        showProtocol: function() {
            switch(this.protocol) {
                case EventPacketProtocol.UNKNOWN:
                    return i18n.t("unknown");
                    break;
                case EventPacketProtocol.TCP:
                    return i18n.t("tcp");
                    break;
                case EventPacketProtocol.UDP:
                    return i18n.t("udp");
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

var packetListView = CollectionView.extend({
    template: _.template(DetailsPacketListTpl),
    className: 'panel panel-primary',
    childViewContainer: 'tbody',
    childView: packetListItemView,
    templateContext: {
        ...i18n,
        showModelCount: function() {
            return this.collection.length;
        }
    },
    serializeData: function() {
        var data = CollectionView.prototype.serializeData.apply(this, arguments);
        data.collection = this.collection;
        return data;
    }
});

const EventDetailsView = View.extend({
    template: _.template(EventDetailsTpl),
    className: 'container-fluid',
    regions: {
        dataList: 'div.detailsDataList',
        interactionList: 'div.detailsInteractionList',
        packetList: 'div.packetList'
    },
    events: {
        'click button.btn-default': function() {
            radio.request('view:content').getRegion('overlay').empty();
        }
    },
    templateContext: {...i18n, ...EventTemplateHelpers},
    initialize: function() {
        this.eventDetails = this.model.getDetailsAndPackets();
        // bind to the details collection, because we split that one into data details and interaction details further below
        this.listenTo(this.eventDetails.details, 'reset', this.updateDetails);
        // re-render on packet changes, because the visibility of the whole packet list might change when the first packet is added
        this.listenTo(this.eventDetails.packets, 'reset', this.updateDetails);
    },
    updateDetails: function() {
        var dataDetails = new EventDetails(this.eventDetails.details.filter(function(m) {
            return m.get('type') === EventDetailType.GENERIC;
        }));
        var interactionDetails = new EventDetails(this.eventDetails.details.filter(function(m) {
            return m.get('type') === EventDetailType.INTERACTION;
        }));
        if(dataDetails.length > 0) this.getRegion('dataList').show(new dataListView({collection: dataDetails}));
        if(interactionDetails.length > 0) this.getRegion('interactionList').show(new interactionListView({collection: interactionDetails}));
        if(this.eventDetails.packets.length > 0) this.getRegion('packetList').show(new packetListView({collection: this.eventDetails.packets}));
    }
});

export default EventDetailsView;
