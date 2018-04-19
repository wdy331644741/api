<?php
return [
	//固定格式*********
    'dateFormat' => 'Y-m-d H:00:00',// Y-m-d H:00:00            | Y-m-d H:i:00                |
    'split' => '-1 hours',          //-1 hours(对应上面相隔1小时) | -5 minute(对应上面相隔5分钟)      //相隔多长时间 累加日活 
    //**************
    'afterAdd' => 60//单位（分钟）
];



