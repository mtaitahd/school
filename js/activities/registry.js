/**
 * Maps activity engine keys to implementations + legacy fallbacks
 */
const ActivityRegistry = {
    mango_counting: (c) => ActivityEngines.mango_counting(c),
    number_identification: (c) => ActivityEngines.number_identification(c),
    number_sequencing: (c) => ActivityEngines.number_sequencing(c),
    number_tracing: (c) => ActivityEngines.number_identification({ ...c, min: 0, max: 10 }),
    missing_numbers: (c) => ActivityEngines.missing_numbers(c),
    match_quantity: (c) => ActivityEngines.match_quantity(c),
    dot_to_dot: (c) => ActivityEngines.number_sequencing(c),
    identify_shapes: (c) => ActivityEngines.identify_shapes(c),
    shape_sorting: (c) => ActivityEngines.identify_shapes(c),
    complete_pattern: (c) => ActivityEngines.complete_pattern(c),
    drag_addition: (c) => ActivityEngines.drag_addition(c),
    visual_subtraction: (c) => ActivityEngines.missing_numbers(c),
    number_line: (c) => ActivityEngines.number_identification(c),
    memory_game: (c) => ActivityEngines.match_quantity(c),
    counting: (c) => ActivityEngines.counting(c),
    shapes: (c) => ActivityEngines.identify_shapes(c),
    addition: (c) => ActivityEngines.drag_addition(c),
    subtraction: (c) => ActivityEngines.missing_numbers(c),
    matching: (c) => ActivityEngines.match_quantity(c),
    game: (c) => ActivityEngines.match_quantity(c),
};

function resolveEngine(config, activityType) {
    if (config && config.engine) return config.engine;
    if (activityType === 'counting' && config && config.object === 'mango') return 'mango_counting';
    return activityType;
}

function runActivity(cfg) {
    const data = cfg.activityData || {};
    const engine = resolveEngine(data, cfg.activityType);
    const runner = ActivityRegistry[engine];
    if (runner) {
        ActivityCore.hideMultiRoundUI();
        runner(data);
        return true;
    }
    return false;
}
