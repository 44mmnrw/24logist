from pathlib import Path

source = Path(__file__).with_name('create_platform_documents.py')
namespace = {'__file__': str(source)}
exec(compile(source.read_text(encoding='utf-8').split('\nresults = [')[0], str(source), 'exec'), namespace)
print(namespace['build'](
    'логистРу — Коммерческое предложение.pdf',
    'логистРу · Коммерческое предложение',
    [namespace['offer_1'], namespace['offer_2']],
))
