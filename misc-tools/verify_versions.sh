#!/bin/bash
# Checks the versions of various compilers on the judgehost and the runtimes in the chroot.

CHROOT="/chroot/domjudge"
ERRORS=""

function green {
echo -e "\e[32m$1\e[39m"
}

function red {
echo -e "\e[31m$1\e[39m"
}

function checkversion
{
  echo "Checking for '$1':"
  PROG=`which $2`
  if [ $? -eq 0 ]; then
    CHROOT_VERSIONSTR=$(chroot $CHROOT env $2 $3 2>&1 | grep -m 1 -o -E '[1-9]+\.[0-9]+(\.[0-9]+)*?(_[0-9]+)?(-\w+)?' | head -n1)
    VERSIONSTR=$($PROG $3 2>&1 | grep -m 1 -o -E '[1-9]+\.[0-9]+(\.[0-9]+)*?(_[0-9]+)?(-\w+)?' | head -n1)

    if [ "$VERSIONSTR" == "" ]; then
      red "Unable to determine version of $PROG"
      ERRORS="${ERRORS}  Unknown version \"$PROG\"\n"
    else
      printf "%-20s " $PROG
      echo -ne "\e[32m$VERSIONSTR\e[39m"
      if [ "$CHROOT_VERSIONSTR" != "" ]; then
        echo -ne " ($CHROOT_VERSIONSTR in chroot)"
      fi
      echo ""
    fi

  else
    red "$2 not found"
    ERRORS="${ERRORS}  Tools for $1 not found(Missing $2)\n"
  fi
  echo ""
}

mount -t proc --bind /proc /chroot/domjudge/proc

echo "Checking Compiler Versions"
echo "========================================="
checkversion 'ada'     'gnatmake'   '--version'
checkversion 'c'       'gcc'        '--version'
checkversion 'c++'     'g++'        '--version'
checkversion 'c#'      'mcs'        '--version'
checkversion 'fortran' 'gfortran'   '--version'
checkversion 'haskell' 'ghc'        '--version'
checkversion 'java'    'javac'      '-version'
checkversion 'lua'     'lua'        '-v'
checkversion 'pascal'  'fpc'        '-version'
checkversion 'python2' 'python2'    '--version'
checkversion 'python3' 'python3'    '--version'
checkversion 'scala'   'scalac'     '-version'

echo "Now Checking Runtime Versions"
echo "========================================="
checkversion 'java runtime'  'java'  '-version'
checkversion 'scala runtime' 'scala' '-version'
checkversion 'c# runtime'    'mono'  '--version'

if [ "$ERRORS" != "" ]; then
  red "There were errors found in your setup:"
  echo -e "$ERRORS"
fi

umount /chroot/domjudge/proc
