from pip.util import get_installed_distributions

skips = ['setuptools', 'pip', 'distribute', 'python', 'wsgiref']
for dist in get_installed_distributions(local_only=True, skip=skips):
    print(dist.project_name, dist.version)
