const webpack = require('webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const merge = require('webpack-merge');
const webpackBaseConfig = require('./webpack.base.config.js');
const fs = require('fs');

fs.open('./src/config/env.js', 'w', function (err, fd) {
    const buf = 'export default "development";';
    // fs.write(fd, buf, 0, buf.length, 0, function (err, written, buffer){});
    fs.write(fd, buf, 0, 'utf-8', function (err, written, buffer) { });
});

module.exports = merge(webpackBaseConfig, {
    devtool: '#source-map',
    output: {
        publicPath: '/dist/',
        filename: '[name].js',
        chunkFilename: '[name].chunk.js'
    },
    plugins: [
        new ExtractTextPlugin({
            filename: '[name].css',
            allChunks: true
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: 'vendors',
            filename: 'vendors.js'
        }),
        new HtmlWebpackPlugin({
            filename: '../index.html',
            template: './src/template/index.ejs',
            inject: false
        })
    ],
    devServer: {
        host: '127.0.0.1',
        port: 8081,
        historyApiFallback: false,
        noInfo: true,
        headers: {
            'X-Content-Type': 'text/html; charset=UTF-8',
            'Access-Control-Allow-Origin': '*'
        },
        hotOnly: false,
        disableHostCheck: true,
        proxy: {
            '/api.php': {
                target: 'http://172.16.90.240/api.php',
                changeOrigin: true,
                // pathRewrite: { '^/api': '' }
            }
        }
    }
});