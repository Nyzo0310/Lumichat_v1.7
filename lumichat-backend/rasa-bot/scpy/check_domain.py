# check_domain.py
from rasa.shared.utils.io import read_yaml_file

def main():
    domain = read_yaml_file("domain.yml")

    print("\n=== RESPONSES ===")
    for r in domain.get("responses", {}):
        print(f" - {r}")

    print("\n=== ACTIONS ===")
    for a in domain.get("actions", []):
        print(f" - {a}")

    print("\n=== FORMS ===")
    for f, slots in domain.get("forms", {}).items():
        print(f"Form: {f}")

        # 🚨 highlight if form name looks like a response (utter_*)
        if f.startswith("utter_"):
            print("   ⚠ Looks like a response, not a form! Move this to 'responses:'")

        if isinstance(slots, dict):
            for s in slots.get("required_slots", {}):
                print(f"   - slot: {s}")
        else:
            print("   ⚠ Unexpected format under form, expected dict with required_slots")

if __name__ == "__main__":
    main()
