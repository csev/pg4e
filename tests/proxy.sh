#! /bin//bash

for file in https://swapi.py4e.com https://pokeapi.co https://mbox.dr-chuck.net http://mbox.dr-chuck.net http://www.gutenberg.org/cache/epub/25990/pg25990.txt http://www.gutenberg.org/cache/epub/25990/pg25990.txt
do
        rm -f zap.txt
        wget -T 2 -O zap.txt $file

        if [ -s zap.txt ]
        then
                echo "Success " $file
        else
                echo "FAIL " $file
                exit
        fi

done
rm -f zap.txt

