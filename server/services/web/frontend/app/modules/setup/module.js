import { radio } from 'app/radio';
import HoneySens from 'app/app';
import createRoutingModule from 'app/routing';
import LayoutView from 'app/modules/setup/views/Layout';
import ErrorView from 'app/modules/setup/views/Error';
import LandingView from 'app/modules/setup/views/Landing';
import AdminPasswordView from 'app/modules/setup/views/AdminPassword';
import EndpointView from 'app/modules/setup/views/Endpoint';
import DivisionView from 'app/modules/setup/views/Division';
import FinalizeInstallView from 'app/modules/setup/views/FinalizeInstall';
import UserPasswordView from 'app/modules/setup/views/UserPassword';

var SetupModule = createRoutingModule({
    name: 'setup',
    startWithParent: false,
    rootView: null,
    menuItems: [],
    start: function() {
        console.log('Starting module: setup');
        this.rootView = new LayoutView();
        radio.request('view:content-region').show(this.rootView);

        // register command handlers
        var contentRegion = this.rootView.getRegion('content'),
            router = this.router;

        radio.reply('setup:landing:show', function() {
            contentRegion.show(new LandingView({model: HoneySens.data.system}));
            router.navigate('setup');
        });
        radio.reply('setup:install:show', function(data) {
            switch(parseInt(data.step)) {
                case 1:
                    contentRegion.show(new AdminPasswordView({model: data.model}));
                    break;
                case 2:
                    contentRegion.show(new EndpointView({model: data.model}));
                    break;
                case 3:
                    contentRegion.show(new DivisionView({model: data.model}));
                    break;
                case 4:
                    // Send setup data to server
                    $.ajax({
                        type: 'POST',
                        url: 'api/system/install',
                        data: JSON.stringify(data.model),
                        contentType: 'application/json',
                        success: function() {
                            contentRegion.show(new FinalizeInstallView());
                        },
                        error: function(xhr) {
                            var code;
                            try {code = JSON.parse(xhr.responseText).code}
                            catch(e) {code = 0}
                            contentRegion.show(new ErrorView({model: new Backbone.Model({code: code})}));
                        }
                    });
                    break;
                default:
                    contentRegion.show(new ErrorView());
                    break;
            }
        });
        radio.reply('setup:changepw:show', function() {
            if(HoneySens.data.session.user.get('require_password_change')) {
                contentRegion.show(new UserPasswordView());
                router.navigate('setup/changepw');
            } else radio.request('logout');
        });
    },
    stop: function() {
        console.log('Stopping module: setup');
        radio.stopReplying('setup:landing:show');
        radio.stopReplying('setup:install:show');
        radio.stopReplying('setup:changepw:show');
    },
    routesList: {
        'setup': 'showLanding',
        'setup/install': 'showInstall',
        'setup/changepw': 'changeOwnPassword'
    },
    showLanding: function() {radio.request('setup:landing:show');},
    showInstall: function() {radio.request('setup:install:show', {step: 1, model: new Backbone.Model()})},
    changeOwnPassword: function() {radio.request('setup:changepw:show');}
});

export default HoneySens.module('Setup.Routing', SetupModule);