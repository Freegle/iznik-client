#### releasinator config ####
configatron.product_name = "sdk-core-php"

# Disabling validate:branch task as it checks for `master` branch to be default. We have `namespace-5.3`
Rake::Task["validate:branch"].clear

# List of items to confirm from the person releasing.  Required, but empty list is ok.
configatron.prerelease_checklist_items = [
  "Sanity check the namespace-php-5.3 branch."
]

def validate_version_match()
  if constant_version() != @current_release.version
    Printer.fail("lib/PayPal/Core/PPConstants.php version #{constant_version} does not match changelog version #{@current_release.version}.")
    abort()
  end
  Printer.success("PPConstants.php version #{constant_version} matches latest changelog version.")
end

def validate_tests()
   CommandProcessor.command("vendor/bin/phpunit", live_output=true)
end

configatron.custom_validation_methods = [
  method(:validate_version_match),
  method(:validate_tests)
]

# there are no separate build steps, so it is just empty method
def build_method
end

# The command that builds the sdk.  Required.
configatron.build_method = method(:build_method)

# Creating and pushing a tag will automatically create a release, so it is just empty method
def publish_to_package_manager(version)
end

# The method that publishes the sdk to the package manager.  Required.
configatron.publish_to_package_manager_method = method(:publish_to_package_manager)


def wait_for_package_manager(version)
end

# The method that waits for the package manager to be done.  Required
configatron.wait_for_package_manager_method = method(:wait_for_package_manager)

# Whether to publish the root repo to GitHub.  Required.
configatron.release_to_github = true

def constant_version()
  f=File.open("lib/PayPal/Core/PPConstants.php", 'r') do |f|
    f.each_line do |line|
      if line.match (/SDK_VERSION = \'\d*\.\d*\.\d*\'/) # SDK_VERSION = '1.7.1'
        return line.strip.split('= ')[1].strip.split('\'')[1]
      end
    end
  end
end
