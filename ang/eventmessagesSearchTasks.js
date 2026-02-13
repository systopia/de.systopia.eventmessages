(function (angular, CRM) {

  function getSelectedIds($scope) {
    if (!$scope.model) {
      return [];
    }

    if (!$scope.model.ids) {
      return [];
    }

    var ids = $scope.model.ids;
    if (!Array.isArray(ids)) {
      return [];
    }

    if (ids.length === 0) {
      return [];
    }

    var result = [];
    for (var i = 0; i < ids.length; i++) {
      var value = ids[i];
      var id = parseInt(value, 10);
      if (!isNaN(id) && id > 0) {
        result.push(id);
      }
    }

    return result;
  }

  function redirectTo($scope, $window, path) {
    var ids = getSelectedIds($scope);
    if (!ids.length) {
      return;
    }

    var url = CRM.url(path, {
      participant_ids: ids.join(','),
      returnUrl: $window.location.href
    });

    $window.location.href = url;
  }

  var module = angular.module('eventmessagesSearchTasks', CRM.angRequires('eventmessagesSearchTasks'));

  module.controller('EventmessagesRedirectEmailCtrl', function ($scope, $window, $timeout) {
    $timeout(function () {
      redirectTo($scope, $window, 'civicrm/eventmessages/participant/email');
    }, 0);
  });

  module.controller('EventmessagesRedirectLetterCtrl', function ($scope, $window, $timeout) {
    $timeout(function () {
      redirectTo($scope, $window, 'civicrm/eventmessages/participant/letter');
    }, 0);
  });

})(angular, CRM);
