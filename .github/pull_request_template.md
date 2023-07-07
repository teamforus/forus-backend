## Changes description
<!--- Describe shortly changes here if:
       - your solution differs from issue desrciption
       - there are advices from development side for QA or other stakeholders
-->

## Developers checklist
- [ ] **Autotests are passed locally**
- [ ] **Migration rollback works** - if there is migration, check rollback
- [ ] **Autotests are added**:
   - bugfix - unit/feature test for this scenario if possible
   - new small/medium feature - simple feature test for positive scenarios

## QA checklist
- [ ] **Translations are done**
- [ ] **Endpoint is fast on dump** - if there is new endpoint or changed query, endpoints that are using changed query - working fast on production dump, <2s 
