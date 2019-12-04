import json

data = {}
data['name'] = 'Chuck'
data['phone'] = {}
data['phone']['type'] = 'intl';
data['phone']['number'] = '+1 734 303 4456';
data['email'] = {}
data['email']['hide'] = 'yes'

# Serialize
print(json.dumps(data, indent=4))
