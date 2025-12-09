{
  "holon_id": "UUID",
  "slug": "string",
  "type": "strategy | structure | task | epic | metric | incident",
  "module": "string (optional)",

  "meta": {
    "title": "string",
    "owner": "persona or team",
    "created_at": "timestamp",
    "updated_at": "timestamp",
    "priority": "low | medium | high | critical",
    "status": "draft | active | completed | deprecated"
  },

  "W": {
    "purpose": "string",
    "value_system": "string",
    "success_definition": "string",
    "kpi": ["string"],
    "okr": {
      "objective": "string",
      "key_results": ["string"]
    },
    "will": "string (W가 가진 핵심 의지)"
  },

  "X": {
    "context": "string",
    "current_state": "string",
    "heartbeat": "hourly | daily | weekly | realtime",
    "signals": ["string"],
    "constraints": ["string"],
    "will": "string"
  },

  "S": {
    "resources": ["string"],
    "dependencies": ["holon_id"],
    "access_points": ["string"],
    "structure_model": "string",
    "ontology_ref": ["string"],
    "readiness_score": "0-1 float",
    "will": "string"
  },

  "P": {
    "procedure_steps": [
      {
        "step_id": "UUID",
        "description": "string",
        "inputs": ["string"],
        "expected_outputs": ["string"],
        "tools_required": ["string"]
      }
    ],
    "optimization_logic": "string",
    "will": "string"
  },

  "E": {
    "execution_plan": [
      {
        "action_id": "UUID",
        "action": "string",
        "eta_hours": "number",
        "role": "PM | BE | FE | DS | ML | Designer | Agent"
      }
    ],
    "tooling": ["string"],
    "edge_case_handling": ["string"],
    "will": "string"
  },

  "R": {
    "reflection_notes": ["string"],
    "lessons_learned": ["string"],
    "success_path_inference": "string",
    "future_prediction": "string",
    "will": "string"
  },

  "T": {
    "impact_channels": ["string"],
    "traffic_model": "string",
    "viral_mechanics": "string",
    "bottleneck_points": ["string"],
    "will": "string"
  },

  "A": {
    "abstraction": "string",
    "modularization": ["string"],
    "automation_opportunities": ["string"],
    "integration_targets": ["holon_id"],
    "resonance_logic": "string",
    "will": "string"
  },

  "links": {
    "parents": ["holon_id"],
    "children": ["holon_id"],
    "related": ["holon_id"],
    "conflicts": ["holon_id"],
    "supersedes": ["holon_id"],
    "version": "integer"
  }
}
