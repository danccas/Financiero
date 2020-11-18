UNIX=$(($(date +%s%N)/1000000))
URL='https://apis.interbank.pe/eureca/api/notification/total?_='$UNIX


curl -i $URL \
  -H 'Connection: keep-alive' \
  -H 'Pragma: no-cache' \
  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36' \
  -H 'Ocp-Apim-Trace: true' \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'Cache-Control: no-cache' \
  -H 'Authorization: bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjaWQiOiI4OCIsInJ1YyI6IjIwNjAyNDk3NTE5IiwicHJmbCI6IjAiLCJleHAiOjE1OTA3Mjg5NjMsImlzcyI6IkV1cmVjYSIsImF1ZCI6IkV1cmVjYSJ9.-3bkczgE8hBmMAeJllIdU02K_sw0gVrNmI85gvVsEjs' \
  -H 'Ocp-Apim-Subscription-Key: c9664a560c184e8cb857e5d2a7efabc4' \
  -H 'Expires: Sat, 01 Jan 2000 00:00:00 GMT' \
  -H 'Origin: https://cobrosimple.interbank.pe' \
  -H 'Sec-Fetch-Site: same-site' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://cobrosimple.interbank.pe/home' \
  -H 'Accept-Language: en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5,und;q=0.4,ja;q=0.3' \
  --compressed

curl -i $URL \
  -H 'Connection: keep-alive' \
  -H 'Pragma: no-cache' \
  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36' \
  -H 'Ocp-Apim-Trace: true' \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'Cache-Control: no-cache' \
  -H 'Authorization: bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjaWQiOiI4OCIsInJ1YyI6IjIwNjAyNDk3NTE5IiwicHJmbCI6IjAiLCJleHAiOjE1OTA3Mjg5NjMsImlzcyI6IkV1cmVjYSIsImF1ZCI6IkV1cmVjYSJ9.-3bkczgE8hBmMAeJllIdU02K_sw0gVrNmI85gvVsEjs' \
  -H 'Ocp-Apim-Subscription-Key: c9664a560c184e8cb857e5d2a7efabc4' \
  -H 'Expires: Sat, 01 Jan 2000 00:00:00 GMT' \
  -H 'Origin: https://cobrosimple.interbank.pe' \
  -H 'Sec-Fetch-Site: same-site' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://cobrosimple.interbank.pe/home' \
  -H 'Accept-Language: en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5,und;q=0.4,ja;q=0.3' \
  --compressed
