const http = require('http');
const express = require('express');
const fs = require('fs');

let server = http.createServer((req, res) => {
    res.writeHead('200', { 'Content-Type': 'text/html; charset=UTF-8' });
    if ('/' == req.url) {
        fs.readFile('index.html', (err, fise) => {
            res.end(fise);
        });
    }
});

let io = require('socket.io')(server); //http://127.0.0.1:666/socket.io/socket.io  http://127.0.0.1:666/socket.io/socket.io.js

io.on('connection', (ws) => {

    //console.log("【有1个】客户端链接了， 【时间】：", new Date()); //注：每次访问都是独立的，所以 每次访问一次就会生成一个ws对象

    io.emit('huida', "【有1个游客进入聊天室】， 【时间】：" + new Date());

    ws.on('hello', (msg) => {

        console.info('服务器到到的内容：', msg);

        //ws.emit('huida', '嗯嗯，欢迎光临！');  //这里如果用ws.meit() 就是一对一的返回(就是谁请求的，就返回给谁);

        io.emit('huida', msg);  //这里如果用io.meit() 就是一对多的返回(就是给所有请求都返回);
    });

});

server.listen('666', () => {
    console.log('监听666端口，请打开：http://localhost:666');
});
//server.listen('6068', '172.28.125.171');


