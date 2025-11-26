beforeEach(() => {
  jest.resetModules();
  jest.useRealTimers();
  document.head.innerHTML = '';
  document.body.innerHTML = '';
  sessionStorage.clear();
  delete window.HelpAssistant;
  delete window.HelpAssistantContext;
  window.alert = jest.fn();
});
