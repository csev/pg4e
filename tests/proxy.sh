#! /bin//bash

for file in https://swapi.py4e.com https://pokeapi.co https://mbox.dr-chuck.net http://mbox.dr-chuck.net https://www.gutenberg.org/ebooks/25990 https://www.gutenberg.org/ebooks/25990.txt.utf-8 http://www.gutenberg.org/ebooks/25990 http://www.gutenberg.org/ebooks/25990.txt.utf-8
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
