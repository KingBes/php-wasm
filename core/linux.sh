v -cc gcc -o .\php_wasm.c .\php_wasm.v 
v -cc gcc -shared .\php_wasm.v -o ..\lib\linux\wasm.so