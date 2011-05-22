=============================
Blockdiag MediaWiki Extension
=============================

requirement
===========

- blockdiag_
- mediawiki >1.16

.. _blockdiag: http://tk0miya.bitbucket.org/blockdiag/build/html/

install
=======

1. Copy blockdiag.php to ${MEDIAWIKI_ROOT}/extension/ ::

   $ sudo cp blockdiag.php ${MEDIAWIKI_ROOT}/extension/

2. Add line to LocalSettings.php ::

   + require_once("$IP/extensions/blockdiag.php");

example
=======

::

        <blockdiag>
        {
                A -> B -> C
                     B -> D -> E
        }
        </blockdiag>


known issues
============


