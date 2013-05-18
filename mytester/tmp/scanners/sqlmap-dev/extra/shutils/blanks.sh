#!/bin/bash

# $Id: blanks.sh 2249 2010-11-03 14:32:37Z stamparm $

# Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
# See the file 'doc/COPYING' for copying permission

# Removes trailing spaces from blank lines inside project files
find ../../. -type f -iname '*.py' -exec sed -i 's/^[ \t]*$//' {} \;
