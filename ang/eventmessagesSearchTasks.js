(function (angular, CRM, ts) {

  function getSelectedIds($scope) {
    if (!$scope.model) {
      return [];
    }

    if (!$scope.model.ids) {
      return [];
    }

    const ids = $scope.model.ids;
    if (!Array.isArray(ids)) {
      return [];
    }

    if (ids.length === 0) {
      return [];
    }

    const result = [];
    for (let i = 0; i < ids.length; i++) {
      const value = ids[i];
      const id = parseInt(value, 10);
      if (!isNaN(id) && id > 0) {
        result.push(id);
      }
    }

    return result;
  }

  function redirectTo($scope, $window, path) {
    const ids = getSelectedIds($scope);
    if (!ids.length) {
      return;
    }

    const url = CRM.url(path, {
      participant_ids: ids.join(','),
      returnUrl: $window.location.href
    });

    $window.location.href = url;
  }

  const module = angular.module('eventmessagesSearchTasks', CRM.angRequires('eventmessagesSearchTasks'));

  module.controller('EventmessagesRedirectEmailCtrl', function ($scope, $window, $timeout) {
    $scope.ts = ts;
    $timeout(function () {
      redirectTo($scope, $window, 'civicrm/eventmessages/participant/email');
    }, 0);
  });

  module.controller('EventmessagesRedirectLetterCtrl', function ($scope, $window, $timeout) {
    $scope.ts = ts;
    $timeout(function () {
      redirectTo($scope, $window, 'civicrm/eventmessages/participant/letter');
    }, 0);
  });

})(angular, CRM, CRM.ts('de.systopia.eventmessages'));
