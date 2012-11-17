=============================
Blockdiag MediaWiki Extension
=============================

requirement
===========

- blockdiag_ (or seqdiag, actdiag, nwdiag)
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

If you want to use other *diag tools, specify a name before "{", like "seqdiag {".

::

       <blockdiag>
       seqdiag {
               A -> B;
                    B -> C;
       }
       </blockdiag>

known issues
============


