<!DOCTYPE HTML>
<html>
<head>
  <meta charset='utf-8'>
  <title>Fatal Error!</title>
  <style>
    body {
      padding:         5px 0 0 0;
      margin:          0;
      font:            .7em Tahoma, Arial, sans-serif;
      line-height:     1.7em;
      color:           #454545;
      background:      -webkit-radial-gradient(white 25%, transparent 16%) 0 0, -webkit-radial-gradient(white 25%, transparent 16%) 8px 8px, -webkit-radial-gradient(rgba(0, 0, 0, .1) 15%, transparent 20%) 0 1px, -webkit-radial-gradient(rgba(0, 0, 0, .1) 15%, transparent 20%) 8px 9px, rgba(250, 250, 250, .5);
      background-size: 16px 16px;
    }

    #msgbox {
      width:       80%;
      margin:      0 auto;
      font-weight: bold;
      text-align:  center;
    }

    .err_msg {
      margin:                 10px 0;
      background-attachment:  scroll;
      background-clip:        border-box;
      background:             rgb(224, 6, 6);
      background:             -moz-linear-gradient(top, rgba(224, 6, 6, 1) 1%, rgba(247, 105, 84, 1) 100%);
      background:             -webkit-gradient(linear, left top, left bottom, color-stop(1%, rgba(224, 6, 6, 1)), color-stop(100%, rgba(247, 105, 84, 1)));
      background:             -webkit-linear-gradient(top, rgba(224, 6, 6, 1) 1%, rgba(247, 105, 84, 1) 100%);
      background:             -o-linear-gradient(top, rgba(224, 6, 6, 1) 1%, rgba(247, 105, 84, 1) 100%);
      background:             -ms-linear-gradient(top, rgba(224, 6, 6, 1) 1%, rgba(247, 105, 84, 1) 100%);
      background:             linear-gradient(top, rgba(224, 6, 6, 1) 1%, rgba(247, 105, 84, 1) 100%);
      filter:                 progid:DXImageTransform.Microsoft.gradient(startColorstr = '#e00606', endColorstr = '#f76954', GradientType = 0);
      -moz-background-origin: padding-box;
      -background-origin:     padding-box;
      border:                 1px solid rgb(205, 10, 10);
      border-radius:          4px;
      color:                  rgb(255, 255, 255);
      display:                block;
      line-height:            27px;
      font-family:            Verdana, Arial, sans-serif;
      font-size:              11px;
      outline:                rgb(255, 255, 255) none 0;
      -moz-box-shadow:        0 2px 3px 1px rgba(10, 10, 10, .5);
      box-shadow:             0 2px 3px 1px rgba(10, 10, 10, .5);
    }

    .left {
      text-align: left;
    }

    .bold {
      font-weight: bolder;
      font-size:   12px;
    }
  </style>
</head>
<body>
<div id='content'>
  <div id='msgbox' style="opacity:1;height:auto" >
    <div class="err_msg">{{$message}}</div>
    <pre class="left bold">{{$debug}}</pre>
  </div>
</div>
</body>
</html>
