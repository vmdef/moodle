Collection of maintenance tips
==============================

How to make all themes tagged with the Purpose:Interface label as requested by
Gavin

    INSERT INTO local_plugins_desc_values (descid, pluginid, value)
    SELECT 1, p.id, 'Interface' FROM local_plugins_plugin p LEFT JOIN
    local_plugins_desc_values dv ON dv.pluginid = p.id AND dv.descid = 1 AND
    dv.value = 'Interface' WHERE p.type = 'theme' AND dv.id IS NULL;
