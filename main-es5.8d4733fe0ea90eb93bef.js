<!DOCTYPE html><html lang="en"><head>
  <meta charset="utf-8">
  <title>УТМ</title>
  <base href="/app">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="favicon.ico">

  <!-- https://github.com/aws/aws-amplify/issues/678 fix: -->
  <!-- https://github.com/sockjs/sockjs-client/issues/439 fix: -->
  <script>
    if (global === undefined) {
      var global = window;
    }
  </script>
  <!-- https://github.com/aws/aws-amplify/issues/678 fix end-->
  <!-- https://github.com/sockjs/sockjs-client/issues/439 fix end-->
  <style type="text/css">
    body, html {
      height: 100%;
    }
    .app-loading {
      position: relative;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
    }
    .app-loading .spinner {
      height: 200px;
      width: 200px;
      animation: rotate 2s linear infinite;
      transform-origin: center center;
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      margin: auto;
    }
    .app-loading .spinner .path {
      stroke-dasharray: 1, 200;
      stroke-dashoffset: 0;
      animation: dash 1.5s ease-in-out infinite;
      stroke-linecap: round;
      stroke: #d25851;
    }
    @keyframes rotate {
      100% {
        transform: rotate(360deg);
      }
    }
    @keyframes dash {
      0% {
        stroke-dasharray: 1, 200;
        stroke-dashoffset: 0;
      }
      50% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -35px;
      }
      100% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -124px;
      }
    }
  </style>
<style>.app-loading .logo{width:150px;height:100px;background:url(egais_utm_primary.1111896167973c52bcb1.svg) 50% no-repeat;}body,html{height:100vh;overflow:hidden;}body{margin:0;font-family:Roboto,Helvetica Neue,sans-serif;}*{-webkit-box-sizing:border-box;box-sizing:border-box;}@-webkit-keyframes spinner{to{-webkit-transform:rotate(1turn);transform:rotate(1turn);}}@keyframes spinner{to{-webkit-transform:rotate(1turn);transform:rotate(1turn);}}.spinner:before{content:"";-webkit-box-sizing:border-box;box-sizing:border-box;position:absolute;top:50%;left:50%;width:20px;height:20px;margin-top:-10px;margin-left:-10px;border-radius:50%;border:2px solid #fff;border-top-color:#0b5ca6;-webkit-animation:spinner .8s linear infinite;animation:spinner .8s linear infinite;}</style><link rel="stylesheet" href="styles.dfa36b6bea6eae14b927.css" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="styles.dfa36b6bea6eae14b927.css"></noscript></head>
<body>
  <app-root>
    <div class="app-loading">
      <div class="logo"></div>
      <svg class="spinner" viewBox="25 25 50 50">
        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"></circle>
      </svg>
    </div>
  </app-root>
<script src="runtime-es2015.008e36ae6a8b35235fa9.js" type="module"></script><script src="runtime-es5.008e36ae6a8b35235fa9.js" nomodule defer></script><script src="polyfills-es5.d4a7c2eca1e5da3ab61e.js" nomodule defer></script><script src="polyfills-es2015.14757d30492bc5097ad2.js" type="module"></script><script src="scripts.2dc46feda87c1802ff0a.js" defer></script><script src="main-es2015.8d4733fe0ea90eb93bef.js" type="module"></script><script src="main-es5.8d4733fe0ea90eb93bef.js" nomodule defer></script>

</body></html>