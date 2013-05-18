#!/bin/bash

# $Id: id.sh 2249 2010-11-03 14:32:37Z stamparm $

# Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
# See the file 'doc/COPYING' for copying permission

# Adds SVN property 'Id' to project files
find ../../. -type f -name "*.py" -exec svn propset svn:keywords "Id" '{}' \;