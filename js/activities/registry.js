/**
 * Maps activity engine keys to implementations + nursery engine routing
 */
const ActivityRegistry = {
    mango_counting: (c) => ActivityEngines.mango_counting(c),
    pattern_counting: (c) => ActivityEngines.pattern_counting(c),
    number_identification: (c) => ActivityEngines.number_identification(c),
    number_sequencing: (c) => ActivityEngines.number_sequencing(c),
    number_tracing: (c) => ActivityEngines.number_identification({ ...c, min: 1, max: 9 }),
    missing_numbers: (c) => ActivityEngines.missing_numbers(c),
    match_quantity: (c) => ActivityEngines.match_quantity(c),
    dot_to_dot: (c) => ActivityEngines.number_sequencing(c),
    identify_shapes: (c) => ActivityEngines.identify_shapes(c),
    shape_sorting: (c) => ActivityEngines.identify_shapes({ ...c, sort_by_size: true }),
    complete_pattern: (c) => ActivityEngines.complete_pattern(c),
    drag_addition: (c) => ActivityEngines.drag_addition(c),
    visual_subtraction: (c) => ActivityEngines.visual_subtraction(c),
    object_recognition: (c) => ActivityEngines.object_recognition(c),
    number_line: (c) => ActivityEngines.number_identification(c),
    memory_game: (c) => ActivityEngines.match_quantity(c),
    counting: (c) => ActivityEngines.counting(c),
    shapes: (c) => ActivityEngines.identify_shapes(c),
    addition: (c) => ActivityEngines.drag_addition(c),
    subtraction: (c) => ActivityEngines.visual_subtraction(c),
    matching: (c) => ActivityEngines.match_quantity(c),
    game: (c) => ActivityEngines.math_game(c),
    math_game: (c) => ActivityEngines.math_game(c),
    objects: (c) => ActivityEngines.object_recognition(c),
    spec_count_objects: (c) => ActivityEngines.spec_count_objects(c),
    spec_zero_plate: (c) => ActivityEngines.spec_zero_plate(c),
    spec_zero_drag: (c) => ActivityEngines.spec_zero_drag(c),
    spec_zero_tap: (c) => ActivityEngines.spec_zero_tap(c),
    spec_ten_tap: (c) => ActivityEngines.spec_ten_tap(c),
    spec_ten_drag: (c) => ActivityEngines.spec_ten_drag(c),
    spec_ten_match: (c) => ActivityEngines.spec_ten_match(c),
    spec_ten_balloon: (c) => ActivityEngines.spec_ten_balloon(c),
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
