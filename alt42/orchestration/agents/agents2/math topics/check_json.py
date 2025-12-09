#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

with open('16 Number_of_cases_and_probability_ontology.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

precedes = [l for l in data['links'] if l['type'] == 'precedes']
depends = [l for l in data['links'] if l['type'] == 'dependsOn']

print(f'총 링크: {len(data["links"])}')
print(f'precedes: {len(precedes)}')
print(f'dependsOn: {len(depends)}')
print(f'\n처음 5개 dependsOn:')
for i, link in enumerate(depends[:5]):
    print(f'  {i+1}. {link["source"]} -> {link["target"]}')

