<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>帖子查看图片</title>
</head>
<body>
    <div class="help-menu">
        @foreach($imgLists as $img)
            <img src={{ $img }} /> </li>
        @endforeach
    </div>
</body>
</html>
