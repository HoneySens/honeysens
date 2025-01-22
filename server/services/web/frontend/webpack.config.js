const HtmlWebpackPlugin = require("html-webpack-plugin");
const webpack = require("webpack");

module.exports = {
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
    mode: "development",
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
                use: ["style-loader", "css-loader"]
            },
        ]
    },
    output: {
        asyncChunks: false,
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
    ],
    resolve: {
        alias: {
            validator: "bootstrap-validator"
        },
        modules: [".", "./node_modules", "./vendor"],
    }
}
