while True:
    txt = input("Enter a string: ")
    if len(txt) < 1: break

    hv = 0;
    for let in txt:

        hv = ((hv << 1) ^ ord(let)) & 0xffffff;

        if ( hv < 2000 ) : 
            print(let, format(ord(let), '08b'), format(hv,'16b'), 
                    format(ord(let), '03d') , hv)
    print(format(hv, '08x'), hv)
