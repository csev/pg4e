
curl -X POST \
  'https://charlesseve-deno-first-10.deno.dev/kv/set/books/fiction/Bob' \
  -H "Content-Type: application/json" \
  -d '{"title":"Bob Was Way Fun", "author":"William Shakespeare", "year":1600}'
