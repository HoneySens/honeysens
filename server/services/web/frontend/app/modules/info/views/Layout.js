import { radio } from 'app/radio';
import HoneySens from 'app/app';
import LayoutTpl from 'app/modules/info/templates/Layout.tpl';
import 'app/views/regions';

HoneySens.module('Info.Views', function(Views, HoneySens, Backbone, Marionette, $, _) {
    Views.Layout = Marionette.View.extend({
        template: _.template(LayoutTpl),
        regions: {
            content: 'div.content'
        },
        initialize: function() {
            this.listenTo(radio, 'info:shown', function() {
                this.$el.find('span.title').html('HoneySens');
            });
        }
    });
});

export default HoneySens.Info.Views.Layout;
