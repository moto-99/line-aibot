# -*- coding: utf-8 -*-
import pkgutil

tmp =  'python lib:'
for m in pkgutil.iter_modules():
    tmp += m[1] + ' '
print tmp
