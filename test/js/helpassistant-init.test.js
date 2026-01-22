const flushPromises = () => new Promise(resolve => setTimeout(resolve, 0));

const createIntroMock = () => {
  const events = {};
  const introInstance = {
    setOptions: jest.fn(),
    onbeforechange: fn => { events.before = fn; },
    onafterchange: fn => { events.after = fn; },
    onexit: fn => { events.exit = fn; },
    oncomplete: fn => { events.complete = fn; },
    start: jest.fn(),
    currentStep: jest.fn(() => 0)
  };

  const introFactory = jest.fn(() => introInstance);
  introFactory.tour = jest.fn(() => introInstance);

  return { introFactory, introInstance, events };
};

const loadModule = () => {
  require('../../asset/js/helpassistant-init.js');
  return window.HelpAssistant.__test;
};

describe('HelpAssistant init script', () => {
  test('ensureMaterialIcons injects the icon font only once', () => {
    const api = loadModule();

    api.ensureMaterialIcons();
    api.ensureMaterialIcons();

    const links = document.head.querySelectorAll('link[data-helpassistant-icons]');
    expect(links).toHaveLength(1);
    expect(links[0].rel).toBe('stylesheet');
  });

  test('getToursMapUrl returns fallback when no context URL is set', () => {
    const api = loadModule();
    delete window.HelpAssistantContext;

    expect(api.getToursMapUrl()).toBe('/admin/help-assistant/tours-map');
  });

  test('getToursMapUrl returns injected URL from context', () => {
    const api = loadModule();
    window.HelpAssistantContext = {
      controller: 'items',
      action: 'browse',
      toursMapUrl: '/medusa/mediateca/admin/help-assistant/tours-map'
    };

    expect(api.getToursMapUrl()).toBe('/medusa/mediateca/admin/help-assistant/tours-map');
  });

  test('loadToursConfig returns tours map and caches the request', async () => {
    const api = loadModule();
    api.resetToursConfigCache();
    window.HelpAssistantContext = { controller: 'test', action: 'test', toursMapUrl: '/admin/help-assistant/tours-map' };

    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ tours: { 'items:browse': { steps: [] } } })
    });

    const first = await api.loadToursConfig();
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(fetch).toHaveBeenCalledWith('/admin/help-assistant/tours-map');
    expect(first).toEqual({ 'items:browse': { steps: [] } });

    const second = await api.loadToursConfig();
    expect(fetch).toHaveBeenCalledTimes(1);
    expect(second).toBe(first);
  });

  test('loadToursConfig falls back to an empty object when the map is missing', async () => {
    const api = loadModule();
    api.resetToursConfigCache();

    global.fetch = jest.fn().mockResolvedValue({
      ok: false,
      json: () => Promise.resolve({})
    });

    const tours = await api.loadToursConfig();
    expect(tours).toEqual({});
  });

  test('getTourInfo returns unavailable when no context is present', async () => {
    const api = loadModule();
    const info = await api.getTourInfo();

    expect(info).toEqual({ available: false, tourConfig: null });
  });

  test('getTourInfo resolves the tour for the current controller/action', async () => {
    const api = loadModule();
    api.resetToursConfigCache();
    window.HelpAssistantContext = { controller: 'items', action: 'browse' };

    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ tours: { 'items:browse': { steps: [{ intro: 'Hola' }] } } })
    });

    const info = await api.getTourInfo();

    expect(info.available).toBe(true);
    expect(info.tourConfig).toEqual({ steps: [{ intro: 'Hola' }] });
  });

  test('setIconState toggles icon colors, labels, and availability', () => {
    const api = loadModule();
    const button = document.createElement('button');
    const icon = document.createElement('span');

    icon.className = 'material-symbols-outlined';
    button.appendChild(icon);

    api.setIconState(button, true);
    expect(button.dataset.tourAvailable).toBe('true');
    expect(button.title).toBe('Start guided tour');
    expect(button.style.opacity).toBe('1');
    expect(icon.style.color).toMatch(/1a73e8|26[, ]+115[, ]+232/);

    api.setIconState(button, false);
    expect(button.dataset.tourAvailable).toBe('false');
    expect(button.title).toBe('No tour available for this page');
    expect(button.style.opacity).toBe('0.7');
    expect(icon.style.color).toMatch(/9e9e9e|158[, ]+158[, ]+158/);
  });

  test('injectTourButton creates the trigger button when a logo and context exist', async () => {
    const api = loadModule();
    api.resetToursConfigCache();

    document.body.innerHTML = '<header><div class="logo"></div></header>';
    window.HelpAssistantContext = { controller: 'items', action: 'browse' };

    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ tours: { 'items:browse': { steps: [] } } })
    });

    api.injectTourButton();
    await flushPromises();

    const button = document.getElementById('helpassistant-start-tour');
    expect(button).not.toBeNull();
    expect(button.getAttribute('data-controller')).toBe('items');
    expect(button.getAttribute('data-action')).toBe('browse');
    expect(button.dataset.tourAvailable).toBe('true');
  });

  test('runTour saves progress when a step requires navigation', () => {
    jest.useFakeTimers();
    const api = loadModule();
    const { introFactory, introInstance, events } = createIntroMock();
    let currentStep = 1;

    introInstance.currentStep = jest.fn(() => currentStep);
    global.introJs = introFactory;

    api.runTour({ steps: [{ intro: 'One', redirect: '/next' }, { intro: 'Two' }] }, 0);

    expect(events.before).toBeDefined();
    const result = events.before();
    expect(result).toBe(false);

    const stored = JSON.parse(sessionStorage.getItem('helpassistant_tour'));
    expect(stored.nextStep).toBe(1);
    expect(stored.config.steps).toHaveLength(2);

    jest.clearAllTimers();
  });

  test('checkContinueTour resumes a stored tour from the saved step', () => {
    jest.useFakeTimers();
    const api = loadModule();
    const { introFactory, introInstance } = createIntroMock();

    global.introJs = introFactory;

    const config = { steps: [{ intro: 'First' }, { intro: 'Second' }] };
    sessionStorage.setItem('helpassistant_tour', JSON.stringify({ config: config, nextStep: 1 }));

    api.checkContinueTour();
    jest.runAllTimers();

    expect(introFactory.tour).toHaveBeenCalled();
    expect(introInstance.setOptions).toHaveBeenCalledWith(expect.objectContaining({
      steps: [{ intro: 'Second' }]
    }));
    expect(introInstance.start).toHaveBeenCalled();
  });
});
