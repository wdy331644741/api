#!/bin/bash
php ../artisan --env=production --timeout=300 queue:listen
