#!/bin/bash
keys=$(openssl ecparam -name secp256k1 -genkey -noout | openssl ec -text -noout 2> /dev/null)

# extract private key in hex format, removing newlines, leading zeroes and semicolon 
priv=$(printf "%s\n" $keys | grep priv -A 3 | tail -n +2 | tr -d '\n[:space:]:' | sed 's/^00//')

# extract public key in hex format, removing newlines, leading '04' and semicolon 
pub=$(printf "%s\n" $keys | grep pub -A 5 | tail -n +2 | tr -d '\n[:space:]:' | sed 's/^04//')

# get the keecak hash, removing the trailing ' -' and taking the last 40 chars
# https://github.com/maandree/sha3sum
platform=$(uname)

if [[ $platform == 'Linux' ]]; then
    addr=0x$(echo $pub | ./linux/keccak-256sum -x -l | tr -d ' -' | tail -c 41) 
elif [[ $platform == 'Darwin' ]]; then
    addr=0x$(echo $pub | ./darwin/keccak-256sum -x -l | tr -d ' -' | tail -c 41) 
fi

echo '{'
echo '    "private_key": "'$priv'",'
echo '    "public_key": "'$pub'",'
echo '    "address": "'$addr'"'
echo '}'