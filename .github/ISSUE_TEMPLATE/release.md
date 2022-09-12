---
name: Release
about: Release issue
title: 'Release v0.XX.X'
labels: 'release, ops'
assignees: 'lexlog, RobinMeles, GerbenBosschieter'

---

# Description
This is the issue for releasing the next version of [forus](https://github.com/teamforus/forus). It should help to communicate about release activity, keep on track of progress and finish all necessary steps.

The full process of the release is described here - [doc link](https://docs.google.com/document/d/1bvpxMAcFuh9_JRewJtHTxnqIeGST_kQgGwJytbFaFCw/edit#heading=h.xjgo9pp5rjan)

#### Goals
- To prepare, do and finish the release
- To do this on time and with the required quality

#### Definition of done
- Release is successfully deployed on production ( or cancelled )
- There are no post-release steps left

# Sub-tasks
### Preparation
- [ ] **All PR-s are reviewed and merged into develop branch**
    - [ ] [backend](https://github.com/teamforus/forus-backend/pulls)
    - [ ] [frontend](https://github.com/teamforus/forus-frontend/pulls)
- [ ] **Release report is prepare**
    - [ ] [Zenhub report](https://app.zenhub.com/workspaces/sprint-5f61c6c0a53fb84e755c82f6/reports/release?release=Z2lkOi8vcmFwdG9yL1JlbGVhc2UvNzk4MTA) is complete
    - [ ] (if needed) List of new help center articles is sent to @MichaelForus
    - [ ] (if needed) New manual are described in [Test Quality](https://web.testquality.com/site/forus/project/12011/tests)
- [ ] **Release PRs are created**
    - [ ] [backend](https://github.com/teamforus/forus-backend/pulls)
    - [ ] [frontend](https://github.com/teamforus/forus-frontend/pulls)
    - [ ] Release note in PRs are described

### Staging deployment and acceptance testing
- [ ] **Release is deployed on staging**
    - [ ] [Jenkins](https://jenkins.forus.io/job/staging/)
    - [ ] Migration
    - [ ] Manual post-deploy steps are done and described in the issue
- [ ] **Acceptance testing is finished**
    - [ ] Test run is prepared
    - [ ] All tests are assigned
    - [ ] All tests are finished

### Production deployment
- [ ] **No critical bugs left since staging**
- [ ] **Release branches are merged**
- [ ] **Deployment on production is done**
    - [ ] [Jenkins](https://jenkins.forus.io/job/production/)
    - [ ] Migration
    - [ ] (if needed) One-time configurations are done
- [ ] **Production is working without regress**

### Post-release actions
- [ ] Release notes in Github are done
- [ ] Release notes in Stonly are done
- [ ] Master branch is merged into develop
- [ ] New tests are described in Test Quality or sent to [tech.debt](https://docs.google.com/document/d/1jhSXnK2rg-UzrKi9zkC00_vxNINFe0OcyXMFy7KcH-8/edit)
- [ ] Help center articles are described
- [ ] All new bugs are fix or reported in GitHub


