#!/bin/bash
basepath=$(cd `dirname $0`; pwd)
php $basepath/../artisan --env=production --timeout=300 queue:listen
