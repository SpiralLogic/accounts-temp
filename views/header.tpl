<!DOCTYPE HTML>
<html {{#$class}}class='{{$class}}'{{/$class}} {{#$lang_dir}}dir='{{$lang_dir}}'{{/$lang_dir}}>
<head>
  <meta charset='utf-8'>
  <title>{{$title}}</title>
  <script>document.documentElement.className += ' js'</script>
  <link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>
  {{#$stylesheets}}
    <link href='{{.}}' rel='stylesheet'/>
  {{/$stylesheets}}
  {{#$script}}
    <script src='{{.}}'></script>
  {{/$script}}
</head>
<body {{#$body_class}}class='{{$body_class}}'{{/$body_class}}>
<div id='content'>
