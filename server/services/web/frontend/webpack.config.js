const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const HtmlWebpackPlugin = require("html-webpack-plugin");
const webpack = require("webpack");

module.exports = (env, options) => {
    const devMode = options.mode !== "production";

    return {
        devServer: {
            server: 'http',
            port: 8081,
            hot: true,
            client: {
                webSocketURL: {
                    port: 443,
                    protocol: "wss"
                }
            }
        },
        entry: "./main.js",
        mode: devMode ? "development" : "production",
        module: {
            rules: [
                {
                    test: /\.(html|tpl)$/i,
                    loader: "html-loader",
                    options: {
                        esModule: false  // Parse *.tpl file contents directly into string variables
                    }
                },
                {
                    test: /\.(png|svg|jpg|jpeg|gif)$/i,
                    type: "asset/resource"
                },
                {
                    test: /loading.css$/i,
                    type: "asset/resource",
                },
                {
                    test: /\.css$/i,
                    exclude: /loading.css$/i,
                    use: [
                        devMode ? "style-loader" : MiniCssExtractPlugin.loader,
                        "css-loader"
                    ]
                },
            ]
        },
        output: {
            filename: "out.js"
        },
        plugins: [
            new HtmlWebpackPlugin({
                template: "./assets/index.html"
            }),
            new webpack.ProvidePlugin({
                _: "underscore",
                $: "jquery",
                Backgrid: "backgrid",
                jQuery: "jquery"
            })
        ].concat(devMode ? [] : [new MiniCssExtractPlugin()]),
        resolve: {
            alias: {
                validator: "bootstrap-validator"
            },
            modules: [".", "./node_modules", "./vendor"],
        }
    };
}
