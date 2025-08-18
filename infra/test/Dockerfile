FROM grafana/k6:0.53.0

# define diretório de trabalho
WORKDIR /rinha-test

# copia apenas os arquivos de teste
COPY rinha.js requests.js package.json ./

# define entrypoint default para rodar o teste
ENTRYPOINT ["k6", "run", "rinha.js"]
