#!/bin/bash

cd `dirname $0` 


#### Ask user ####

echo "This script tries to update your MOTP-AS installation. Please backup all files and database before proceeding."
echo 
echo "Proceed? (y/n)"
read YESNO

[ "$YESNO" != y ] && exit 1
echo

DIR="";
while [ "$DIR" = "" ]; do
  echo "In which directory are your html/php files installed?"
  read DIR;
  [ -f $DIR/INC/footer.php ] || DIR=""
  grep -q version $DIR/INC/footer.php || DIR=""
done


#### patch html/php ####

echo -n > migrate.sql
while true; do

  OLDVER=`grep VERSION $DIR/INC/config.php | cut -f2 -d\"`
  [ "$OLDVER" = "" ] && OLDVER=`grep version $DIR/INC/footer.php | sed 's/.*version //' | cut -f1 -d" "`

  echo "Looking for warnings for update to version $NEWVER ... "
  NOTE=`ls -1 warning_$OLDVER-*.txt 2>/dev/null`
  if [ "$NOTE" != "" ]; then
	echo "PLEASE READ THE FOLLOWING WARNINGS:"
	echo "============================================================="
	more $NOTE
	echo "============================================================="
	echo
	echo "Press RETURN for starting update."
	read 
  fi

  echo "Looking for patch for version $OLDVER ... "

  PATCH=`ls -1 patches/update_$OLDVER-*.patch 2>/dev/null`
  [ "$PATCH" != "" ] && echo "  $PATCH"
  SQL=`ls -1 MySQL/migrate_$OLDVER-*.sql 2>/dev/null`
  [ "$SQL" != "" ] && echo "  $SQL"
  [ "$PATCH" = "" ] && echo "No patches found. " && break;

  echo

  echo "Applying patch ..."
  cat $PATCH | (cd $DIR; patch -p 2)
  echo

  echo "Copying files ..."
  for file in `cat $PATCH | grep ^Files | grep differ | cut -f2 -d" " | sed 's:MOTP.*HTML/::'`; do
	cp ../HTML/$file $DIR/$file
  done
  echo

  [ "$SQL" != "" ] && cat $SQL >> migrate.sql

done
echo

#### patch database ####

test -s migrate.sql || { rm migrate.sql; exit 0; }

echo "Applying database scripts. Please specify database access:"
echo -n "database: [motp] "; read DATABASE; [ "$DATABASE" = "" ] && DATABASE=motp
echo -n "user: [root] "; read USER; [ "$USER" = "" ] && USER=root
echo -n "password: [motp] "; read -s PASSWORD; [ "$PASSWORD" = "" ] && PASSWORD=motp
echo

mysql -v --user="$USER" --password="$PASSWORD" "$DATABASE" < migrate.sql
rm migrate.sql
echo

