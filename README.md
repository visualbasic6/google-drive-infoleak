**edit: this has been patched. took ~5 years**

**how to install and use**

1. use google developers console to generate api credentials *after enabling drive api* on a new project
2. edit index.php and insert your clientid, clientsecret and redirecturi starting at line 26
3. your redirecturi value must be the url that points to index.php
4. navigate to index.php after hosting it on a local or remote webserver

![lulz](https://i.imgur.com/nBBQdf3.png)

**full name information leak in google drive**

in 2015 i discovered and reported this leak found in both mapsengine and drive. mapsengine was patched, but it was evidently a feature in drive. many email accounts across several providers whose names aren't visible on g+ or in account recovery procedures become retrievable. this issue was disclosed to google back in 2015.

**fyi**

this reveals *way* more full names than account recovery procedures, the "to" field in new emails, etc. and as such [seclists.org commentators](https://seclists.org/fulldisclosure/2015/Jan/95) were and still are [dead wrong](https://youtu.be/gwpFaU7FwtQ?t=117) ¯\\_(ツ)_/¯

![lulz](https://i.imgur.com/RqXaPti.png)

lulz full disclosure
