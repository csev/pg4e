# In this hash function we use multiplication to make sure that
# transposing two letters does not lead to the same hash

while True:
    txt = input("Enter a string: ")
    if len(txt) < 1: break

    hv = 0
    pos = 0
    mod = 2
    for let in txt:
        pos = (pos + 1) % mod
        hv = (hv + (pos * ord(let))) % 1000000
        print(let, pos, ord(let), hv)
    print(hv, txt)
