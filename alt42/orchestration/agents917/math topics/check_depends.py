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

# 중등->고등 연결 확인
node_map = {n['id']: n for n in data['nodes']}
stage1_to_stage3 = []
for l in depends:
    source = node_map.get(l['source'])
    target = node_map.get(l['target'])
    if source and target:
        if source['stage'] >= 3 and target['stage'] == 1:
            stage1_to_stage3.append((source, target, l))

print(f'\n중등(Stage 1) -> 고등(Stage 3+) 연결: {len(stage1_to_stage3)}개')
for source, target, link in stage1_to_stage3[:5]:
    print(f'  {source["label"][:25]} (S{source["stage"]}) -> {target["label"][:25]} (S{target["stage"]})')

# 순열/조합 -> 경우의 수 연결 확인
perm_comb_to_cases = []
for l in depends:
    source = node_map.get(l['source'])
    target = node_map.get(l['target'])
    if source and target:
        source_text = source['label'].lower() + ' ' + source.get('description', '').lower()
        target_text = target['label'].lower() + ' ' + target.get('description', '').lower()
        if ('순열' in source_text or '조합' in source_text) and '경우의수' in target_text:
            perm_comb_to_cases.append((source, target))

print(f'\n순열/조합 -> 경우의 수 연결: {len(perm_comb_to_cases)}개')
for source, target in perm_comb_to_cases[:5]:
    print(f'  {source["label"][:25]} (S{source["stage"]}) -> {target["label"][:25]} (S{target["stage"]})')

