-- Удаляем таблицу логов действий (имеет внешние ключи, поэтому удаляем первой)
DROP TABLE IF EXISTS `#__crm_action_log`;

DROP TABLE IF EXISTS `#__crm_stage_actions`;

-- Удаляем таблицу компаний
DROP TABLE IF EXISTS `#__crm_companies`;

-- Удаляем таблицу стадий
DROP TABLE IF EXISTS `#__crm_stages`;

-- Удаляем таблицу действий
DROP TABLE IF EXISTS `#__crm_actions`;

